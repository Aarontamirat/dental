import { chromium } from 'playwright';
import { readFileSync, mkdirSync, existsSync, readdirSync } from 'node:fs';
import { join } from 'node:path';

let executablePath=process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE;
if(!executablePath && process.platform==='win32' && process.env.LOCALAPPDATA){const root=join(process.env.LOCALAPPDATA,'ms-playwright');if(existsSync(root)){const directory=readdirSync(root).filter(n=>/^chromium-\d+$/.test(n)).sort((a,b)=>Number(b.split('-')[1])-Number(a.split('-')[1]))[0];if(directory)executablePath=join(root,directory,'chrome-win64','chrome.exe');}}
const browser = await chromium.launch({ executablePath });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const errors=[];page.on('pageerror', error=>errors.push(error.message));
const paths=['/','/services','/services/general-dentistry','/about','/doctors','/doctors/demo-general-dentist','/articles','/articles/small-habits-healthier-smiles','/branches','/branches/addis-ababa-demo','/gallery','/faq','/testimonials','/appointments','/contact','/pages/privacy','/?lang=am'];
for (const path of paths) {
  const response=await page.goto(`http://127.0.0.1:8000${path}`);
  console.log(`${response.status()} ${path}`);
}
mkdirSync('.tools/previews',{recursive:true});
await page.goto('http://127.0.0.1:8000/?lang=en');
async function loadImages(){for(const image of await page.locator('img').all()){await image.scrollIntoViewIfNeeded();await image.evaluate(img=>img.decode());}await page.evaluate(()=>document.fonts.ready);await page.evaluate(()=>window.scrollTo({top:0,behavior:'instant'}));await page.waitForFunction(()=>window.scrollY===0);}
await loadImages();await page.screenshot({path:'.tools/previews/home-desktop.png',fullPage:true});await page.screenshot({path:'.tools/previews/home-hero.png'});
await page.setViewportSize({width:390,height:844});await loadImages();await page.screenshot({path:'.tools/previews/home-mobile.png',fullPage:true});
const credentials=JSON.parse(readFileSync('.tools/local-access.json','utf8'));
await page.goto('http://127.0.0.1:8000/login');await page.getByLabel('Email address').fill(credentials.email);await page.getByLabel('Password',{exact:true}).fill(credentials.password);await page.getByRole('button',{name:'Sign in',exact:true}).click();await page.waitForURL('**/admin');
await page.setViewportSize({width:1440,height:1000});await page.getByRole('heading',{name:'Hello, Demo.'}).waitFor();await page.getByText('Published services',{exact:true}).waitFor();await page.screenshot({path:'.tools/previews/admin-desktop.png',fullPage:true});
await page.setViewportSize({width:390,height:844});await page.screenshot({path:'.tools/previews/admin-mobile.png',fullPage:true});
console.log('Browser errors:',JSON.stringify(errors));await browser.close();
