import { defineConfig } from '@playwright/test';
import { existsSync,readdirSync } from 'node:fs';
import { join } from 'node:path';

let executablePath=process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE;
if(!executablePath && process.platform==='win32' && process.env.LOCALAPPDATA) {
  const root=join(process.env.LOCALAPPDATA,'ms-playwright');
  if(existsSync(root)){const directory=readdirSync(root).filter(n=>/^chromium-\d+$/.test(n)).sort((a,b)=>Number(b.split('-')[1])-Number(a.split('-')[1]))[0];if(directory)executablePath=join(root,directory,'chrome-win64','chrome.exe');}
}
export default defineConfig({testDir:'tests/browser',fullyParallel:false,workers:1,timeout:30000,reporter:'list',use:{baseURL:'http://127.0.0.1:8011',launchOptions:{executablePath},screenshot:'only-on-failure',trace:'retain-on-failure'},webServer:{command:'node tools/e2e-server.mjs',url:'http://127.0.0.1:8011',reuseExistingServer:false,timeout:60000}});
