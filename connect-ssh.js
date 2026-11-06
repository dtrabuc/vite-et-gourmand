#!/usr/bin/env node
/**
 * Connexion SSH à l'hébergement, avec SSH_HOST, SSH_USERNAME et SSH_PASSWORD lus dans .env.
 *
 *   node connect-ssh.js              teste la connexion
 *   node connect-ssh.js "commande"   exécute la commande sur le serveur
 *
 * Le mot de passe passe par SSH_ASKPASS : il n'apparaît ni dans les arguments ni dans un fichier.
 * Une clé d'hôte déjà connue qui aurait changé fait échouer la connexion.
 */
const { spawnSync } = require('node:child_process');
const { mkdtempSync, readFileSync, rmSync, writeFileSync } = require('node:fs');
const { tmpdir } = require('node:os');
const { join } = require('node:path');

function readEnv(path) {
  const values = {};
  for (const line of readFileSync(path, 'utf8').split(/\r?\n/)) {
    const match = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/);
    if (match) values[match[1]] = match[2].replace(/^(['"])(.*)\1$/, '$2');
  }
  return values;
}

const env = readEnv(join(__dirname, '.env'));
const missing = ['SSH_HOST', 'SSH_USERNAME', 'SSH_PASSWORD'].filter((key) => !env[key]);
if (missing.length > 0) {
  console.error(`Variables manquantes dans .env : ${missing.join(', ')}`);
  process.exit(2);
}

const command = process.argv[2] ?? 'true';
const askpassDir = mkdtempSync(join(tmpdir(), 'connect-ssh-'));
const askpass = join(askpassDir, 'askpass.sh');
writeFileSync(askpass, '#!/bin/sh\nprintf \'%s\\n\' "$CONNECT_SSH_PASSWORD"\n', { mode: 0o700 });

let result;
try {
  result = spawnSync('ssh', [
    '-o', 'ConnectTimeout=15',
    '-o', 'StrictHostKeyChecking=accept-new',
    '-o', 'PubkeyAuthentication=no',
    '-o', 'PreferredAuthentications=password,keyboard-interactive',
    '-o', 'NumberOfPasswordPrompts=1',
    `${env.SSH_USERNAME}@${env.SSH_HOST}`,
    command,
  ], {
    stdio: ['ignore', 'inherit', 'inherit'],
    env: {
      ...process.env,
      SSH_ASKPASS: askpass,
      SSH_ASKPASS_REQUIRE: 'force',
      CONNECT_SSH_PASSWORD: env.SSH_PASSWORD,
    },
  });
} finally {
  rmSync(askpassDir, { recursive: true, force: true });
}

if (result.error || result.status !== 0) {
  if (result.error) console.error(result.error.message);
  console.log('connexion failed');
  process.exit(result.status || 1);
}
console.log('ssh success');
