// Crée le tableau Trello de gestion de projet à partir de backlog-trello.json.
//
// 1. Clé API : https://trello.com/power-ups/admin → créer un Power-Up → "API key".
// 2. Jeton : depuis la page de la clé, lien "Token" (autoriser l'accès).
// 3. Ajouter dans le .env du projet (jamais versionné) :
//      TRELLO_API_KEY=...
//      TRELLO_TOKEN=...
// 4. Lancer : node docs/gestion-projet/trello-import.mjs
//
// Le script crée un NOUVEAU tableau à chaque lancement et affiche son adresse.
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const root = join(here, '..', '..');

// lecture simple du .env (mêmes règles que l'application : clé=valeur, guillemets retirés)
const env = {};
try {
  for (const line of readFileSync(join(root, '.env'), 'utf8').split('\n')) {
    const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/);
    if (m) env[m[1]] = m[2].replace(/^["']|["']$/g, '');
  }
} catch { /* pas de .env : on regarde les variables d'environnement */ }

const key = process.env.TRELLO_API_KEY || env.TRELLO_API_KEY;
const token = process.env.TRELLO_TOKEN || env.TRELLO_TOKEN;
if (!key || !token) {
  console.error('TRELLO_API_KEY et TRELLO_TOKEN sont requis (dans .env). Voir l’en-tête du script.');
  process.exit(1);
}

const data = JSON.parse(readFileSync(join(here, 'backlog-trello.json'), 'utf8'));

async function api(method, path, params = {}) {
  const url = new URL('https://api.trello.com/1' + path);
  url.searchParams.set('key', key);
  url.searchParams.set('token', token);
  for (const [k, v] of Object.entries(params)) url.searchParams.set(k, String(v));
  for (let attempt = 0; attempt < 5; attempt++) {
    const res = await fetch(url, { method, headers: { Accept: 'application/json' } });
    if (res.status === 429) { await new Promise((r) => setTimeout(r, 1500 * (attempt + 1))); continue; }
    if (!res.ok) throw new Error(`${method} ${path} : HTTP ${res.status} ${await res.text()}`);
    return res.json();
  }
  throw new Error(`${method} ${path} : trop de requêtes`);
}

const board = await api('POST', '/boards/', {
  name: data.board, desc: data.description, defaultLists: false, defaultLabels: false, prefs_background: 'red',
});
console.log('Tableau créé :', board.url);

const labels = {};
for (const l of data.labels) labels[l.name] = (await api('POST', '/labels', { idBoard: board.id, name: l.name, color: l.color })).id;

const lists = {};
let pos = 1;
for (const name of data.lists) lists[name] = (await api('POST', '/lists', { idBoard: board.id, name, pos: pos++ })).id;

let count = 0;
for (const c of data.cards) {
  const card = await api('POST', '/cards', {
    idList: lists[c.list], name: c.name, desc: c.desc || '', pos: 'bottom',
    idLabels: (c.labels || []).map((n) => labels[n]).filter(Boolean).join(','),
  });
  if (c.checklist) {
    const cl = await api('POST', '/checklists', { idCard: card.id, name: 'À faire' });
    const done = c.list === 'Terminé';
    for (const item of c.checklist) await api('POST', `/checklists/${cl.id}/checkItems`, { name: item, checked: done });
  }
  count++;
}
console.log(`${data.lists.length} listes, ${data.labels.length} étiquettes, ${count} cartes.`);
console.log('Ouvrir :', board.url);
