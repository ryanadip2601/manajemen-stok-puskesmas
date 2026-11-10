// Create Supabase Auth logins for the SIMBAR users in a Laravel SQLite backup, keeping their current passwords.
//
//   npm run import:auth -- <backup.sqlite>                       list what would happen
//   npm run import:auth -- <backup.sqlite> --apply               create missing logins and link them to their profiles
//   npm run import:auth -- <backup.sqlite> --apply --refresh     also copy the latest Laravel password into existing logins
//
// Laravel stores bcrypt hashes; Supabase Auth accepts them as password_hash, so nobody has to reset a password.
// No e-mail is sent. An Auth account that already exists but is not linked to the profile is reported, never linked:
// someone may have registered with that address. Uses the isolated Supabase CLI login in %LOCALAPPDATA%\SIMBAR-CLI.
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { DatabaseSync } from 'node:sqlite';

const PROJECT = 'vlcftyrexirkrswodmsk';
const SUPABASE = `https://${PROJECT}.supabase.co`;
const args = process.argv.slice(2);
const backup = args.find((arg) => !arg.startsWith('--'));
const apply = args.includes('--apply');
const refresh = args.includes('--refresh');
assert.ok(backup, 'Provide the path to the SQLite backup.');

const env = { ...process.env, SUPABASE_HOME: path.join(process.env.LOCALAPPDATA ?? '', 'SIMBAR-CLI', 'supabase'), SUPABASE_NO_KEYRING: '1' };
delete env.SUPABASE_ACCESS_TOKEN;
const npx = process.platform === 'win32' ? 'npx.cmd' : 'npx';
const cli = (cliArgs) => execFileSync(npx, ['--yes', 'supabase', ...cliArgs], { env, encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'], shell: process.platform === 'win32' });
const json = (text) => JSON.parse(text.slice(text.search(/[[{]/)));

function sql(statement, write = false) {
  const dir = mkdtempSync(path.join(tmpdir(), 'simbar-auth-'));
  try {
    const file = path.join(dir, 'query.sql');
    writeFileSync(file, statement);
    return json(cli(['db', 'query', `--remote=${PROJECT}`, '--agent', 'no', '--output-format', 'json', '--file', file, write ? '--yes' : '--read-only']));
  } finally {
    rmSync(dir, { recursive: true, force: true });
  }
}
const quote = (value) => "'" + String(value).replace(/'/g, "''") + "'";

const source = new DatabaseSync(path.resolve(backup), { readOnly: true });
const legacy = source.prepare('SELECT id, name, email, password FROM users ORDER BY id').all()
  .map((row) => ({ ...row, email: String(row.email).trim().toLowerCase() }));
source.close();
assert.ok(legacy.every((row) => /^\$2[aby]\$\d\d\$[./A-Za-z0-9]{53}$/.test(row.password)), 'Every Laravel password must be a bcrypt hash.');

const keys = json(cli(['projects', 'api-keys', `--remote=${PROJECT}`, '--reveal', '--agent', 'no', '--output-format', 'json'])).keys;
const service = keys.find((key) => key.name === 'service_role')?.api_key;
assert.ok(service, 'The service_role key is unavailable to the SIMBAR CLI login.');
const admin = { apikey: service, Authorization: 'Bearer ' + service, 'Content-Type': 'application/json' };

async function authRequest(method, route, body) {
  const response = await fetch(SUPABASE + '/auth/v1/admin/' + route, { method, headers: admin, body: body && JSON.stringify(body), redirect: 'error' });
  const result = await response.json().catch(() => null);
  if (!response.ok) throw new Error(`Supabase Auth ${method} ${route.split('?')[0]} failed (${response.status}): ${result?.msg ?? result?.message ?? ''}`);
  return result;
}

const authUsers = new Map();
for (let page = 1; ; page++) {
  const { users } = await authRequest('GET', `users?page=${page}&per_page=200`);
  for (const user of users) authUsers.set(String(user.email).toLowerCase(), user);
  if (users.length < 200) break;
}
const profiles = new Map(sql('SELECT id, lower(email) AS email, auth_user_id, is_approved FROM simbar.users WHERE id > 0;').map((row) => [row.email, row]));

const plan = { create: [], refresh: [], linked: [], conflict: [], missingProfile: [] };
for (const user of legacy) {
  const profile = profiles.get(user.email);
  const login = authUsers.get(user.email);
  if (!profile) plan.missingProfile.push(user);
  else if (!login) plan.create.push(user);
  else if (profile.auth_user_id === login.id) (refresh ? plan.refresh : plan.linked).push({ ...user, authId: login.id });
  else plan.conflict.push(user);
}
console.log(`Laravel users: ${legacy.length}. Create: ${plan.create.length}, already linked: ${plan.linked.length + plan.refresh.length}` +
  `${refresh ? ` (password refresh: ${plan.refresh.length})` : ''}, conflicts: ${plan.conflict.length}, not in Supabase: ${plan.missingProfile.length}.`);
for (const user of plan.conflict) console.log(`  ! ${user.email}: an unlinked Supabase login already exists - check it before linking by hand.`);
for (const user of plan.missingProfile) console.log(`  ! ${user.email}: no SIMBAR profile in Supabase - run import:database first.`);

if (!apply) {
  console.log('Nothing was changed. Add --apply to create the logins.');
} else {
  for (const user of plan.create) {
    const created = await authRequest('POST', 'users', {
      email: user.email, password_hash: user.password.replace(/^\$2y\$/, '$2a$'), email_confirm: true, user_metadata: { name: user.name },
    });
    sql(`UPDATE simbar.users SET auth_user_id = ${quote(created.id)} WHERE lower(email) = ${quote(user.email)} AND auth_user_id IS NULL;`, true);
  }
  for (const user of plan.refresh) {
    await authRequest('PUT', 'users/' + user.authId, { password_hash: user.password.replace(/^\$2y\$/, '$2a$') });
  }
  const linked = sql('SELECT count(*)::int AS n FROM simbar.users WHERE auth_user_id IS NOT NULL AND id > 0;')[0].n;
  console.log(`Created ${plan.create.length} logins${refresh ? `, refreshed ${plan.refresh.length} passwords` : ''}. Linked profiles: ${linked}.`);
}
