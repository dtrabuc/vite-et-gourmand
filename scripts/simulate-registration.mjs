#!/usr/bin/env node

/**
 * Test de bout en bout de l'inscription client et des liens de réinitialisation.
 *
 *   node scripts/simulate-registration.mjs             SMTP local de capture, aucun envoi externe
 *   node scripts/simulate-registration.mjs --ethereal  boîte Ethereal jetable, relue en IMAP
 *
 * L'application tourne depuis une copie temporaire du projet, sur la base configurée
 * dans .env, qui doit être locale. Les comptes de test (codex-sim-…@example.invalid)
 * sont supprimés à la fin, même en cas d'échec. Mots de passe et jetons ne sont jamais affichés.
 */
import { cp, mkdtemp, readFile, readdir, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { spawn, spawnSync } from 'node:child_process';
import { createServer } from 'node:net';
import { connect as tlsConnect } from 'node:tls';
import { createHash, randomUUID } from 'node:crypto';

const project = resolve(import.meta.dirname, '..');
const useEthereal = process.argv.includes('--ethereal');
const envPath = join(project, '.env');
const envText = await readFile(envPath, 'utf8');
const env = Object.fromEntries(envText.split(/\r?\n/).flatMap((line) => {
  const trimmed = line.trim();
  if (!trimmed || trimmed.startsWith('#') || !trimmed.includes('=')) return [];
  const i = trimmed.indexOf('=');
  return [[trimmed.slice(0, i).trim(), trimmed.slice(i + 1).trim().replace(/^['"]|['"]$/g, '')]];
}));

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
let passed = 0;
const failures = [];
function check(label, ok, detail = '') {
  if (ok) { passed += 1; console.log(`  OK    ${label}`); }
  else { failures.push(label); console.log(`  ÉCHEC ${label}${detail ? ` (${detail})` : ''}`); }
}
const section = (title) => console.log(`\n${title}`);

if (!['127.0.0.1', 'localhost', '::1'].includes(env.DB_HOST || '127.0.0.1')) {
  throw new Error('Arrêt : DB_HOST n’est pas local. Aucun compte de test créé.');
}

/* ----- accès direct à la base : c'est elle qui fait foi, pas les pages HTML ----- */
const phpDb = String.raw`
$E=[];foreach(file($argv[1],FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $l){$l=trim($l);if($l===''||$l[0]==='#'||!str_contains($l,'='))continue;[$k,$v]=array_map('trim',explode('=',$l,2));$E[$k]=trim($v,"\"'");}
$op=$argv[2];$email=$argv[3]??'';
if($email!==''&&!preg_match('/^codex-sim-[^@]+@example\.invalid$/',$email)){fwrite(STDERR,'email-refuse');exit(3);}
$p=new PDO('mysql:host='.$E['DB_HOST'].';port='.($E['DB_PORT']??3306).';dbname='.$E['DB_DATABASE'].';charset=utf8mb4',$E['DB_USERNAME']??'',$E['DB_PASSWORD']??'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$r=null;
switch($op){
 case 'preflight':$r=['migration'=>(bool)$p->query("SHOW COLUMNS FROM users LIKE 'must_change_password'")->rowCount(),'users'=>(int)$p->query('SELECT COUNT(*) FROM users')->fetchColumn()];break;
 case 'get':$s=$p->prepare('SELECT *,TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,reset_token_expires_at) AS reset_ttl FROM users WHERE email=?');$s->execute([$email]);$r=$s->fetch()?:null;if($r){$pw=$argv[4]??null;$r['password_matches']=$pw!==null?password_verify($pw,$r['password']):null;$r['password_is_hash']=(password_get_info($r['password'])['algo']??null)!==null;$r['password_sha']=hash('sha256',$r['password']);unset($r['password']);}break;
 case 'expire':$s=$p->prepare('UPDATE users SET reset_token_expires_at=DATE_SUB(CURRENT_TIMESTAMP,INTERVAL 1 MINUTE) WHERE email=?');$s->execute([$email]);$r=['n'=>$s->rowCount()];break;
 case 'active':$s=$p->prepare('UPDATE users SET is_active=? WHERE email=?');$s->execute([(int)$argv[4],$email]);$r=['n'=>$s->rowCount()];break;
 case 'cleanup':$s=$p->prepare("DELETE FROM users WHERE role='user' AND email LIKE 'codex-sim-%@example.invalid'");$s->execute();$r=['deleted'=>$s->rowCount()];break;
}
echo json_encode($r);
`;
function db(op, email = '', extra) {
  const args = ['-r', phpDb, '--', envPath, op, email];
  if (extra !== undefined) args.push(String(extra));
  const result = spawnSync('php', args, { encoding: 'utf8' });
  if (result.status !== 0) throw new Error(`Accès base impossible (${op}).`);
  return JSON.parse(result.stdout);
}

const before = db('preflight');
if (!before.migration) throw new Error('Arrêt : colonne must_change_password absente. Appliquez la migration puis relancez.');

/* ----- décodage minimal d'un message MIME ----- */
function decodeWords(value) {
  return value.replace(/\r?\n[ \t]+/g, ' ').replace(/=\?UTF-8\?([BQ])\?([^?]*)\?=/gi, (_, enc, text) => (
    enc.toUpperCase() === 'B'
      ? Buffer.from(text, 'base64').toString('utf8')
      : Buffer.from(text.replace(/_/g, ' ').replace(/=([0-9A-F]{2})/gi, (m, h) => String.fromCharCode(parseInt(h, 16))), 'latin1').toString('utf8')
  )).replace(/\?=\s+=\?/g, '');
}
function parseMail(raw) {
  const split = raw.indexOf('\r\n\r\n');
  const head = raw.slice(0, split);
  let body = raw.slice(split + 4);
  const header = (name) => head.match(new RegExp(`^${name}:\\s*((?:.*)(?:\\r\\n[ \\t].*)*)`, 'im'))?.[1] ?? '';
  const encoding = header('Content-Transfer-Encoding').trim().toLowerCase();
  if (encoding === 'base64') body = Buffer.from(body.replace(/\s/g, ''), 'base64').toString('utf8');
  else if (encoding === 'quoted-printable') {
    body = Buffer.from(body.replace(/=\r?\n/g, '').replace(/=([0-9A-F]{2})/gi, (_, h) => String.fromCharCode(parseInt(h, 16))), 'latin1').toString('utf8');
  }
  return { to: header('To'), from: header('From'), subject: decodeWords(header('Subject')), body: body.replace(/^\.\./gm, '.') };
}

/* ----- SMTP local de capture ----- */
const sinkMessages = [];
let smtpServer;
function listen(server) {
  return new Promise((ok, ko) => {
    server.once('error', ko);
    server.listen(0, '127.0.0.1', () => { server.removeListener('error', ko); ok(server.address().port); });
  });
}
function startSink() {
  smtpServer = createServer((socket) => {
    socket.setEncoding('latin1');
    socket.write('220 local-test ESMTP\r\n');
    let buffer = '';
    let dataMode = false;
    socket.on('error', () => {});
    socket.on('data', (chunk) => {
      buffer += chunk;
      for (;;) {
        if (dataMode) {
          const end = buffer.indexOf('\r\n.\r\n');
          if (end < 0) return;
          sinkMessages.push(parseMail(Buffer.from(buffer.slice(0, end), 'latin1').toString('latin1')));
          buffer = buffer.slice(end + 5);
          dataMode = false;
          socket.write('250 message captured\r\n');
          continue;
        }
        const eol = buffer.indexOf('\r\n');
        if (eol < 0) return;
        const command = buffer.slice(0, eol).toUpperCase();
        buffer = buffer.slice(eol + 2);
        if (command.startsWith('EHLO') || command.startsWith('HELO')) socket.write('250-local-test\r\n250 OK\r\n');
        else if (command === 'DATA') { dataMode = true; socket.write('354 end with dot\r\n'); }
        else if (command === 'QUIT') { socket.write('221 Bye\r\n'); socket.end(); }
        else socket.write('250 OK\r\n');
      }
    });
  });
  return listen(smtpServer);
}

/* ----- Ethereal : création de la boîte, puis lecture réelle en IMAP ----- */
async function createEtherealAccount() {
  const response = await fetch('https://api.nodemailer.com/user', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ requestor: 'vite-gourmand-registration-simulation', version: '2.0.0' }),
  });
  if (!response.ok) throw new Error(`Ethereal : création de boîte refusée (HTTP ${response.status}).`);
  const account = await response.json();
  if (account.status !== 'success' || account.smtp?.host !== 'smtp.ethereal.email' || account.imap?.host !== 'imap.ethereal.email') {
    throw new Error('Ethereal : configuration inattendue, arrêt par sécurité.');
  }
  return account;
}
function imapFetchAll(account) {
  return new Promise((ok, ko) => {
    const socket = tlsConnect({ host: account.imap.host, port: account.imap.port, servername: account.imap.host });
    let buffer = Buffer.alloc(0);
    let step = 0;
    let tagCount = 0;
    let waiter = null;
    const timer = setTimeout(() => { socket.destroy(); ko(new Error('IMAP : délai dépassé.')); }, 20000);
    socket.on('error', (e) => { clearTimeout(timer); ko(e); });
    const pump = () => {
      if (!waiter) return;
      const text = buffer.toString('latin1');
      const match = waiter.tag === null ? text.match(/^\* OK.*\r\n/m) : text.match(new RegExp(`^${waiter.tag} (OK|NO|BAD).*\\r\\n`, 'm'));
      if (!match) return;
      const done = waiter;
      waiter = null;
      const out = buffer;
      buffer = Buffer.alloc(0);
      if (match[1] && match[1] !== 'OK') done.ko(new Error(`IMAP : réponse ${match[1]}.`));
      else done.ok(out);
    };
    socket.on('data', (chunk) => { buffer = Buffer.concat([buffer, chunk]); pump(); });
    const wait = (tag) => new Promise((a, b) => { waiter = { tag, ok: a, ko: b }; pump(); });
    const send = (command) => { tagCount += 1; const tag = `a${tagCount}`; socket.write(`${tag} ${command}\r\n`); return wait(tag); };
    (async () => {
      step = 1; await wait(null);
      step = 2; await send(`LOGIN "${account.user}" "${account.pass}"`);
      step = 3; await send('SELECT INBOX');
      step = 4; const found = (await send('UID SEARCH ALL')).toString('latin1');
      const uids = found.match(/^\* SEARCH(.*)$/m)?.[1].trim().split(/\s+/).filter(Boolean) ?? [];
      const messages = [];
      for (const uid of uids) {
        step = 5; const raw = await send(`UID FETCH ${uid} BODY.PEEK[]`);
        const header = raw.toString('latin1').match(/\{(\d+)\}\r\n/);
        if (!header) continue;
        const start = raw.toString('latin1').indexOf(header[0]) + header[0].length;
        messages.push(parseMail(raw.subarray(start, start + Number(header[1])).toString('latin1')));
      }
      socket.write(`z LOGOUT\r\n`);
      socket.end();
      clearTimeout(timer);
      ok(messages);
    })().catch((e) => { clearTimeout(timer); socket.destroy(); ko(new Error(`${e.message} (étape IMAP ${step})`)); });
  });
}

let account = null;
const consumed = new Set();
const keyOf = (m) => `${m.to}|${m.subject}|${createHash('sha256').update(m.body).digest('hex')}`;
/** Attend un message pas encore lu pour ce destinataire : réception réelle, pas seulement « accepté ». */
async function waitForMail(to, subjectPart) {
  for (let attempt = 0; attempt < (useEthereal ? 30 : 30); attempt += 1) {
    const box = useEthereal ? await imapFetchAll(account) : sinkMessages;
    const found = box.find((m) => m.to.includes(to) && m.subject.includes(subjectPart) && !consumed.has(keyOf(m)));
    if (found) { consumed.add(keyOf(found)); return found; }
    await sleep(useEthereal ? 2000 : 100);
  }
  return null;
}
async function mailCount(to) {
  const box = useEthereal ? await imapFetchAll(account) : sinkMessages;
  return box.filter((m) => m.to.includes(to)).length;
}

/* ----- client HTTP avec cookies ----- */
let baseUrl = '';
let cookieJar = new Map();
const newBrowser = () => { cookieJar = new Map(); };
async function request(path, { method = 'GET', form } = {}) {
  const headers = {};
  if (cookieJar.size) headers.Cookie = [...cookieJar].map(([k, v]) => `${k}=${v}`).join('; ');
  let body;
  if (form) { headers['Content-Type'] = 'application/x-www-form-urlencoded'; body = new URLSearchParams(form).toString(); }
  const response = await fetch(`${baseUrl}${path}`, { method, headers, body, redirect: 'manual' });
  for (const setCookie of response.headers.getSetCookie?.() ?? []) {
    const [pair] = setCookie.split(';');
    const i = pair.indexOf('=');
    if (i > 0) cookieJar.set(pair.slice(0, i), pair.slice(i + 1));
  }
  return { status: response.status, location: response.headers.get('location'), text: await response.text() };
}
const csrfFrom = (html) => html.match(/name="csrf_token"\s+value="([^"]+)"/)?.[1] ?? html.match(/name="csrf-token"\s+content="([^"]+)"/)?.[1] ?? '';
async function post(formPath, targetPath, fields) {
  const page = await request(formPath);
  return request(targetPath, { method: 'POST', form: { csrf_token: csrfFrom(page.text), ...fields } });
}
const login = (email, password) => post('/login', '/login', { email, password });
const logout = async () => { const page = await request('/'); await request('/logout', { method: 'POST', form: { csrf_token: csrfFrom(page.text) } }); newBrowser(); };

const stamp = `${Date.now()}-${randomUUID().slice(0, 6)}`;
const mkEmail = (name) => `codex-sim-${name}-${stamp}@example.invalid`;
const STRONG_A = `CodexTest!${randomUUID().slice(0, 8)}aA7`;
const STRONG_B = `CodexReset!${randomUUID().slice(0, 8)}bB8`;
const STRONG_C = `CodexAutre!${randomUUID().slice(0, 8)}cC9`;
const signupFields = (email) => ({
  first_name: 'Simulation', last_name: 'Codex', phone: '06 12 34 56 78', email,
  address: '1 rue du Test, 33000 Bordeaux', privacy_consent: '1',
});

const tempRoot = await mkdtemp(join(tmpdir(), 'vite-gourmand-signup-'));
let phpServer;
let serverErrors = '';
let tempEnv = '';
const clearRateLimits = async () => {
  const dir = join(tempRoot, 'storage', 'cache');
  for (const name of await readdir(dir).catch(() => [])) await rm(join(dir, name), { force: true });
};
const setEnvLine = (text, key, value) => (new RegExp(`^${key}=.*$`, 'm').test(text)
  ? text.replace(new RegExp(`^${key}=.*$`, 'm'), () => `${key}=${value}`)
  : `${text}\n${key}=${value}\n`);

try {
  const smtpPort = useEthereal ? null : await startSink();
  if (useEthereal) account = await createEtherealAccount();
  await cp(project, tempRoot, { recursive: true, filter: (s) => !s.includes('/.git/') && !s.includes('/node_modules') });
  await clearRateLimits();

  const probe = createServer();
  const httpPort = await listen(probe);
  await new Promise((r) => probe.close(r));
  baseUrl = `http://127.0.0.1:${httpPort}`;

  tempEnv = envText;
  const mailSettings = useEthereal
    ? { MAIL_HOST: account.smtp.host, MAIL_PORT: account.smtp.port, MAIL_ENCRYPTION: account.smtp.secure ? 'ssl' : 'tls', MAIL_USERNAME: account.user, MAIL_PASSWORD: account.pass, MAIL_FROM_ADDRESS: account.user }
    : { MAIL_HOST: '127.0.0.1', MAIL_PORT: smtpPort, MAIL_ENCRYPTION: 'none', MAIL_USERNAME: '', MAIL_PASSWORD: '' };
  for (const [key, value] of Object.entries({ APP_URL: baseUrl, MAIL_DRIVER: 'smtp', ...mailSettings })) tempEnv = setEnvLine(tempEnv, key, value);
  await writeFile(join(tempRoot, '.env'), tempEnv, { mode: 0o600 });
  const router = join(tempRoot, 'public', 'router-sim.php');
  await writeFile(router, `<?php\n$path = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);\nif ($path !== __DIR__ && is_file($path)) return false;\nrequire __DIR__ . '/index.php';\n`);
  phpServer = spawn('php', ['-S', `127.0.0.1:${httpPort}`, '-t', join(tempRoot, 'public'), router], { stdio: ['ignore', 'ignore', 'pipe'] });
  phpServer.stderr.setEncoding('utf8');
  phpServer.stderr.on('data', (chunk) => { serverErrors = (serverErrors + chunk).slice(-4000); });
  let ready = false;
  for (let i = 0; i < 60 && !ready; i += 1) {
    try { await (await fetch(`${baseUrl}/register`)).arrayBuffer(); ready = true; } catch { await sleep(100); }
  }
  if (!ready) throw new Error('Le serveur PHP local ne répond pas.');

  console.log(`Mode : ${useEthereal ? 'Ethereal (SMTP STARTTLS + relecture IMAP)' : 'SMTP local de capture'}`);

  /* ===== Inscription refusée : rien ne doit arriver en base ===== */
  section('1. Inscriptions refusées');
  const rejected = mkEmail('refus');
  let res = await request('/register');
  res = await request('/register', { method: 'POST', form: { csrf_token: 'faux', ...signupFields(rejected) } });
  check('sans jeton CSRF valide : HTTP 403', res.status === 403, `HTTP ${res.status}`);
  check('sans jeton CSRF valide : aucune ligne en base', db('get', rejected) === null);
  res = await post('/register', '/register', { ...signupFields(rejected), privacy_consent: '' });
  check('sans consentement : renvoi au formulaire, aucune ligne', res.location === '/register' && db('get', rejected) === null);
  res = await post('/register', '/register', { ...signupFields(rejected), phone: '123' });
  check('téléphone invalide : renvoi au formulaire, aucune ligne', res.location === '/register' && db('get', rejected) === null);

  await clearRateLimits();
  const smtpDown = mkEmail('smtp');
  await writeFile(join(tempRoot, '.env'), setEnvLine(setEnvLine(tempEnv, 'MAIL_HOST', '127.0.0.1'), 'MAIL_PORT', '1'), { mode: 0o600 });
  res = await post('/register', '/register', signupFields(smtpDown));
  check('SMTP en panne : erreur et compte retiré de la base', res.location === '/register' && db('get', smtpDown) === null, `redirection ${res.location}`);
  await writeFile(join(tempRoot, '.env'), tempEnv, { mode: 0o600 });

  /* ===== Inscription réussie ===== */
  section('2. Inscription, base et réception du mail');
  await clearRateLimits();
  newBrowser();
  const email = mkEmail('client');
  res = await post('/register', '/register', { ...signupFields(email), email: email.toUpperCase().replace('EXAMPLE.INVALID', 'example.invalid').replace('CODEX-SIM', 'codex-sim') });
  check('redirection vers /login', res.status === 302 && res.location === '/login', `HTTP ${res.status} ${res.location}`);
  let row = db('get', email);
  check('ligne créée, e-mail en minuscules', row?.email === email);
  check('role=user, is_active=1, must_change_password=1', row?.role === 'user' && Number(row?.is_active) === 1 && Number(row?.must_change_password) === 1);
  check('mot de passe stocké haché', row?.password_is_hash === true);
  check('téléphone normalisé en E.164', row?.phone === '+33612345678', row?.phone);

  const welcome = await waitForMail(email, 'Votre accès');
  check(useEthereal ? 'mail présent dans la boîte (lu en IMAP)' : 'mail reçu par le SMTP local', !!welcome);
  if (!welcome) throw new Error('Mail d’inscription introuvable : arrêt du parcours.');
  check('destinataire et adresse dans le corps', welcome.to.includes(email) && welcome.body.includes(email));
  const temporary = welcome.body.match(/Mot de passe provisoire\s*:\s*([^\r\n]+)/i)?.[1]?.trim() ?? '';
  check('mot de passe provisoire de 18 caractères', temporary.length === 18, `${temporary.length} caractères`);
  check('le mot de passe du mail correspond au hash en base', db('get', email, temporary)?.password_matches === true);

  res = await post('/register', '/register', signupFields(email));
  check('e-mail déjà utilisé : refus', res.location === '/register');
  check('e-mail déjà utilisé : mot de passe inchangé', db('get', email, temporary)?.password_matches === true);

  /* ===== Première connexion ===== */
  section('3. Première connexion et changement obligatoire');
  await clearRateLimits();
  newBrowser();
  res = await login(email, 'MauvaisMotDePasse!1');
  check('mauvais mot de passe : refus', res.location === '/login');
  check('failed_attempts passe à 1 en base', Number(db('get', email)?.failed_attempts) === 1);
  res = await login(email, temporary);
  check('mot de passe provisoire : redirection vers /profile', res.location === '/profile', `${res.status} ${res.location}`);
  check('failed_attempts remis à 0', Number(db('get', email)?.failed_attempts) === 0);
  res = await request('/orders');
  check('GET /orders bloqué vers /profile', res.location === '/profile', `${res.status} ${res.location}`);
  res = await post('/profile', '/profile', { email, first_name: 'Pirate', last_name: 'Codex', phone: '+33612345678', gsm: '+33612345678', address: 'x' });
  check('POST /profile bloqué, base inchangée', res.location === '/profile' && db('get', email)?.first_name === 'Simulation');
  res = await request('/profile');
  check('avertissement affiché sur le profil', res.status === 200 && res.text.includes('mot de passe provisoire'));

  res = await post('/profile', '/password', { current_password: 'Faux!Faux!1234', new_password: STRONG_A, confirm_password: STRONG_A });
  check('mot de passe actuel faux : drapeau maintenu', Number(db('get', email)?.must_change_password) === 1);
  res = await post('/profile', '/password', { current_password: temporary, new_password: 'court', confirm_password: 'court' });
  check('mot de passe faible : refusé, hash inchangé', db('get', email, temporary)?.password_matches === true);
  res = await post('/profile', '/password', { current_password: temporary, new_password: STRONG_A, confirm_password: STRONG_A });
  row = db('get', email, STRONG_A);
  check('changement valide : must_change_password=0 et nouveau hash', Number(row?.must_change_password) === 0 && row?.password_matches === true);
  res = await request('/orders');
  check('/orders accessible après le changement', res.status === 200, `HTTP ${res.status} ${res.location}`);
  await logout();
  await clearRateLimits();
  res = await login(email, temporary);
  check('ancien mot de passe provisoire refusé', res.location === '/login');
  res = await login(email, STRONG_A);
  check('nouveau mot de passe accepté', res.location === '/', `${res.status} ${res.location}`);
  await logout();

  /* ===== Liens sécurisés ===== */
  section('4. Liens de réinitialisation');
  await clearRateLimits();
  const ghost = mkEmail('inconnu');
  res = await post('/forgot-password', '/forgot-password', { email: ghost });
  const flash = (await request('/forgot-password')).text;
  check('adresse inconnue : même message, aucun mail', flash.includes('Si cette adresse existe') && (await mailCount(ghost)) === 0);

  const requestLink = async () => {
    await clearRateLimits();
    await post('/forgot-password', '/forgot-password', { email });
    const mail = await waitForMail(email, 'initialisation');
    const url = mail?.body.match(/https?:\/\/\S+\/reset-password\/\S+/)?.[0] ?? '';
    return { mail, url, token: url.split('/').pop() ?? '' };
  };
  const first = await requestLink();
  check(useEthereal ? 'mail de lien présent dans la boîte (IMAP)' : 'mail de lien reçu', !!first.mail);
  if (!first.url) throw new Error('Lien de réinitialisation introuvable : arrêt du parcours.');
  check('le lien pointe vers APP_URL', first.url.startsWith(`${baseUrl}/reset-password/`));
  check('jeton de 64 caractères hexadécimaux', /^[a-f0-9]{64}$/.test(first.token));
  row = db('get', email);
  const sha = (t) => createHash('sha256').update(t).digest('hex');
  check('la base stocke le SHA-256, jamais le jeton', row?.reset_token_hash === sha(first.token) && row?.reset_token_hash !== first.token);
  check('expiration à 1 h (± 2 min)', Math.abs(Number(row?.reset_ttl) - 3600) <= 120, `${row?.reset_ttl} s`);

  const path = (t) => `/reset-password/${t}`;
  const flip = (t) => (t[0] === 'a' ? 'b' : 'a') + t.slice(1);
  for (const [label, forged] of [
    ['un caractère modifié', flip(first.token)],
    ['jeton aléatoire', 'f'.repeat(64)],
    ['trop court', first.token.slice(0, 32)],
    ['majuscules', first.token.toUpperCase()],
    ['haché stocké utilisé comme jeton', row.reset_token_hash],
    ['injection SQL', encodeURIComponent("' OR '1'='1")],
  ]) {
    res = await request(path(forged));
    check(`GET refusé : ${label}`, res.status === 302 && res.location === '/forgot-password', `${res.status} ${res.location}`);
  }
  res = await request(path(first.token));
  check('GET lien authentique : formulaire affiché', res.status === 200 && res.text.includes('Nouveau mot de passe'));

  const shaBefore = db('get', email).password_sha;
  await clearRateLimits();
  res = await post(path(first.token), path(flip(first.token)), { password: STRONG_B, confirm_password: STRONG_B });
  check('POST avec jeton falsifié : mot de passe inchangé', db('get', email).password_sha === shaBefore);
  res = await post(path(first.token), path(first.token), { password: STRONG_B, confirm_password: 'different' });
  check('confirmation différente : mot de passe inchangé', db('get', email).password_sha === shaBefore);
  res = await post(path(first.token), path(first.token), { password: 'court', confirm_password: 'court' });
  check('mot de passe faible : inchangé, jeton conservé', db('get', email).password_sha === shaBefore && db('get', email).reset_token_hash === sha(first.token));
  res = await post(path(first.token), path(first.token), { password: STRONG_B, confirm_password: STRONG_B });
  check('4e POST en 5 min : HTTP 429 (limitation de débit)', res.status === 429, `HTTP ${res.status}`);
  check('POST bloqué : mot de passe inchangé', db('get', email).password_sha === shaBefore);

  const second = await requestLink();
  check('nouvelle demande : autre jeton', !!second.token && second.token !== first.token);
  res = await request(path(first.token));
  check('nouvelle demande : ancien lien invalidé', res.location === '/forgot-password', `${res.status} ${res.location}`);

  db('expire', email);
  res = await request(path(second.token));
  check('lien expiré : GET refusé', res.location === '/forgot-password', `${res.status} ${res.location}`);
  res = await post('/login', path(second.token), { password: STRONG_B, confirm_password: STRONG_B });
  check('lien expiré : POST sans effet', db('get', email).password_sha === shaBefore);

  const third = await requestLink();
  await clearRateLimits();
  res = await post(path(third.token), path(third.token), { password: STRONG_B, confirm_password: STRONG_B });
  check('lien valide : redirection vers /login', res.location === '/login', `${res.status} ${res.location}`);
  row = db('get', email, STRONG_B);
  check('base : nouveau hash enregistré', row?.password_matches === true && row?.password_sha !== shaBefore);
  check('base : jeton et expiration effacés', row?.reset_token_hash === null && row?.reset_token_expires_at === null);
  res = await request(path(third.token));
  check('lien à usage unique : second accès refusé', res.location === '/forgot-password', `${res.status} ${res.location}`);
  newBrowser();
  await clearRateLimits();
  res = await login(email, STRONG_A);
  check('ancien mot de passe refusé après réinitialisation', res.location === '/login');
  res = await login(email, STRONG_B);
  check('nouveau mot de passe accepté après réinitialisation', res.location === '/', `${res.status} ${res.location}`);
  await logout();

  /* ===== Cas limites de sécurité ===== */
  section('5. Cas limites');
  const stale = await requestLink();
  newBrowser();
  await clearRateLimits();
  await login(email, STRONG_B);
  await post('/profile', '/password', { current_password: STRONG_B, new_password: STRONG_C, confirm_password: STRONG_C });
  check('changement depuis le profil enregistré', db('get', email, STRONG_C)?.password_matches === true);
  await logout();
  res = await request(path(stale.token));
  check('changement depuis le profil : lien en cours invalidé', res.location === '/forgot-password', `${res.status} ${res.location}`);

  const disabledLink = await requestLink();
  db('active', email, 0);
  res = await request(path(disabledLink.token));
  check('compte désactivé : lien refusé', res.location === '/forgot-password', `${res.status} ${res.location}`);
  await clearRateLimits();
  const beforeDisabled = db('get', email).password_sha;
  newBrowser();
  await post('/forgot-password', '/forgot-password', { email });
  check('compte désactivé : aucune nouvelle demande de lien', db('get', email).reset_token_hash === sha(disabledLink.token) && db('get', email).password_sha === beforeDisabled);
  db('active', email, 1);

  await clearRateLimits();
  newBrowser();
  for (let i = 0; i < 5; i += 1) await login(email, 'MauvaisMotDePasse!1');
  row = db('get', email);
  check('5 échecs : compte verrouillé en base', row?.locked_until !== null && Number(row?.failed_attempts) >= 5);
  const lockedLink = await requestLink();
  await clearRateLimits();
  await post(path(lockedLink.token), path(lockedLink.token), { password: STRONG_A, confirm_password: STRONG_A });
  await clearRateLimits();
  newBrowser();
  res = await login(email, STRONG_A);
  check('réinitialisation : le compte verrouillé redevient accessible', res.location === '/', `${res.status} ${res.location}`);
} catch (error) {
  failures.push(`interruption : ${error.message}`);
  console.log(`\nINTERRUPTION : ${error.message}`);
} finally {
  if (phpServer && phpServer.exitCode === null) phpServer.kill('SIGTERM');
  if (smtpServer) await new Promise((r) => { smtpServer.close(() => r()); smtpServer.closeAllConnections?.(); setTimeout(r, 300); });
  section('Nettoyage');
  const cleanup = db('cleanup');
  const after = db('preflight');
  console.log(`  Comptes de test supprimés : ${cleanup.deleted}`);
  check('nombre de comptes en base identique au départ', after.users === before.users, `${before.users} → ${after.users}`);
  await rm(tempRoot, { recursive: true, force: true });
}

console.log(`\nRésultat : ${passed} contrôles réussis, ${failures.length} en échec.`);
if (failures.length) { for (const f of failures) console.log(`  - ${f}`); process.exitCode = 1; }
