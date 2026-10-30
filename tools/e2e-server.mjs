import { randomBytes } from 'node:crypto';
import { mkdirSync, mkdtempSync, writeFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { spawn, spawnSync } from 'node:child_process';

mkdirSync('.tools', { recursive: true });
const directory = mkdtempSync(resolve('.tools/playwright-'));
const database = resolve(directory, 'database.sqlite');
writeFileSync(database, '');
const password = `E2e!${randomBytes(20).toString('hex')}Aa`;
const env = { ...process.env, APP_ENV: 'local', APP_DEBUG: 'false', APP_URL: 'http://127.0.0.1:8011', APP_KEY: `base64:${randomBytes(32).toString('base64')}`, DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'file', SESSION_DRIVER: 'database', MAIL_MAILER: 'array', E2E_PASSWORD: password, E2E_SETUP: '1', ...(existsSync('.tools/php.ini') ? { PHPRC: resolve('.tools/php.ini') } : {}) };
for (const args of [['artisan','migrate','--force','--no-interaction'],['artisan','db:seed','--class=DemoSeeder','--force','--no-interaction'],['tools/e2e-user.php']]) {
  const result = spawnSync('php',args,{env,stdio:'pipe',encoding:'utf8'});
  if(result.status!==0){console.error(result.stdout,result.stderr);process.exit(1);}
}
writeFileSync('.tools/e2e-access.json',JSON.stringify({ email:'e2e@clinic.test',password }),{mode:0o600});
const server=spawn('php',['artisan','serve','--host=127.0.0.1','--port=8011'],{env,stdio:'inherit'});
process.on('SIGTERM',()=>server.kill());process.on('SIGINT',()=>server.kill());server.on('exit',code=>process.exit(code??0));
