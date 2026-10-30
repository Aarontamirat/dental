import {test,expect,type BrowserContext,type Page} from '@playwright/test';
import {readFileSync} from 'node:fs';
import AxeBuilder from '@axe-core/playwright';

let sessionCookies: Awaited<ReturnType<BrowserContext['cookies']>> | undefined;
async function checkAccessibility(page: Page) {
  const results = await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
  expect(results.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>n.target)}))).toEqual([]);
}
async function login(page: Page) {
  if (sessionCookies) {
    await page.context().addCookies(sessionCookies);
    await page.goto('/admin');
  } else {
    const access = JSON.parse(readFileSync('.tools/e2e-access.json', 'utf8'));
    await page.goto('/login');
    await page.getByLabel('Email address').fill(access.email);
    await page.getByLabel('Password', { exact: true }).fill(access.password);
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL('**/admin');
    sessionCookies = await page.context().cookies();
  }
  await expect(page.getByText('Published services', { exact: true })).toBeVisible();
}

test('public pages render, images resolve, and structured data is valid',async({page})=>{
  const errors:string[]=[];page.on('pageerror',e=>errors.push(e.message));
  for(const path of ['/','/services','/services/general-dentistry','/about','/doctors','/articles','/articles/small-habits-healthier-smiles','/branches','/gallery','/faq','/testimonials','/appointments','/contact','/pages/privacy']){const response=await page.goto(path);expect(response?.status(),path).toBe(200);expect(await page.locator('h1').count(),path).toBe(1);const schemas=await page.locator('script[type="application/ld+json"]').allTextContents();if(path.split('/').length>2)expect(schemas.length).toBeGreaterThan(0);for(const source of schemas){expect(JSON.parse(source)['@context'],path).toBe('https://schema.org');}}
  await page.goto('/');for(const image of await page.locator('img').all()){await image.scrollIntoViewIfNeeded();await expect.poll(()=>image.evaluate((img:HTMLImageElement)=>img.complete&&img.naturalWidth>0)).toBe(true);}
  expect(errors).toEqual([]);
});
test('mobile navigation, keyboard escape, and language switching work',async({page})=>{
  for(const width of [320,390,768]){await page.setViewportSize({width,height:844});await page.goto('/');expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth)).toBe(true);}
  await page.setViewportSize({width:390,height:844});await page.getByRole('button',{name:'Explore',exact:true}).click();await expect(page.locator('#mobile-menu')).toBeVisible();await page.keyboard.press('Escape');await expect(page.locator('#mobile-menu')).toBeHidden();
  await page.getByRole('link',{name:'AM',exact:true}).click();await expect(page.locator('html')).toHaveAttribute('lang','am');await expect(page.getByRole('heading',{level:1})).toContainText('ፈገግታ');
});
test('appointment request is stored and can be confirmed through administration',async({page})=>{
  await page.goto('/appointments');await page.getByLabel('Full name').fill('E2E Test Patient');await page.getByLabel('Phone number').fill('0912345678');await page.getByLabel('Email address').fill('e2e-patient@example.test');
  const date=new Date();date.setDate(date.getDate()+7);while(date.getDay()===0)date.setDate(date.getDate()+1);const iso=[date.getFullYear(),String(date.getMonth()+1).padStart(2,'0'),String(date.getDate()).padStart(2,'0')].join('-');
  await page.getByLabel('Preferred date').fill(iso);await page.getByLabel('Preferred time',{exact:false}).fill('10:00');await page.getByRole('checkbox').check();await page.waitForTimeout(2100);await page.getByRole('button',{name:'Send appointment request'}).click();await expect(page.getByRole('heading',{name:'Your request is on its way.'})).toBeVisible();await expect(page.getByText('Your appointment is not yet confirmed.',{exact:false})).toBeVisible();
  await login(page);await page.getByRole('button',{name:'Appointment requests',exact:true}).click();await page.getByRole('button',{name:'View request from E2E Test Patient'}).click();await page.getByLabel('Status',{exact:true}).selectOption('confirmed');await page.getByLabel('Internal notes',{exact:false}).fill('Confirmed in the isolated browser test.');await page.getByRole('button',{name:'Update request'}).click();await expect(page.getByRole('dialog')).toHaveCount(0);await expect(page.locator('.admin-table .status-confirmed')).toBeVisible();
});
test('contact inquiry is submitted and marked resolved',async({page})=>{
  await page.goto('/contact');await page.getByLabel('Full name').fill('E2E Inquiry');await page.getByLabel('Email address').fill('inquiry@example.test');await page.getByLabel('What can we help with?').fill('A clinic question');await page.getByLabel('Your message').fill('Please provide information about a first visit.');await page.getByRole('checkbox').check();await page.waitForTimeout(2100);await page.getByRole('button',{name:'Send message',exact:true}).click();await expect(page.getByRole('heading',{name:/Message received/})).toBeVisible();
  await login(page);await page.getByRole('button',{name:'Contact messages',exact:true}).click();await page.getByRole('button',{name:'View request from E2E Inquiry'}).click();await page.getByLabel('Status',{exact:true}).selectOption('resolved');await page.getByRole('button',{name:'Update request'}).click();await expect(page.locator('.status-resolved')).toBeVisible();
});
test('CMS create, edit, public rendering, and deletion are integrated',async({page})=>{
  await login(page);await page.getByRole('button',{name:'Services',exact:true}).click();await page.getByRole('button',{name:'Add item',exact:true}).click();await page.getByLabel('Name / title').fill('Browser test service');await page.getByLabel(/^Publication/).selectOption('published');await page.getByLabel('Short introduction').fill('A service created in an isolated browser test.');await page.getByRole('textbox',{name:'Rich text content'}).fill('Browser editor content.');await page.getByRole('button',{name:'Save changes',exact:true}).click();await expect(page.getByRole('dialog')).toHaveCount(0);await expect(page.getByText('Browser test service',{exact:true})).toBeVisible();
  await page.getByRole('button',{name:'Edit Browser test service',exact:true}).click();await page.getByRole('textbox',{name:'Rich text content'}).fill('Updated through the rich text editor.');await page.getByRole('button',{name:'Save changes',exact:true}).click();await expect(page.getByRole('dialog')).toHaveCount(0);
  await page.goto('/services/browser-test-service');await expect(page.getByRole('heading',{level:1})).toHaveText('Browser test service');await expect(page.getByText('Updated through the rich text editor.',{exact:true})).toBeVisible();await page.goto('/admin#services');await page.getByRole('button',{name:'Delete Browser test service'}).click();await page.getByRole('button',{name:'Delete item',exact:true}).click();await expect(page.getByText('Browser test service',{exact:true})).toHaveCount(0);
});
test('branding settings persist and homepage section controls work',async({page})=>{
  await login(page);await page.getByRole('button',{name:'Branding & settings'}).click();await page.getByLabel('Clinic name',{exact:false}).fill('Browser Test Clinic');await page.getByRole('button',{name:'Save changes',exact:true}).first().click();await expect(page.getByRole('status')).toContainText('Changes saved.');await page.goto('/');await expect(page.locator('.site-header .brand')).toContainText('Browser Test Clinic');
  await page.goto('/admin#sections');const row=page.locator('.section-editor-row').filter({has:page.getByLabel('hero heading',{exact:true})});await row.getByRole('checkbox').uncheck();await page.getByRole('button',{name:'Save homepage'}).click();await expect(page.getByRole('status')).toContainText('Homepage updated.');await page.goto('/');await expect(page.locator('.hero')).toHaveCount(0);
});
test('media upload, replacement, and deletion work',async({page})=>{
  await login(page);await page.getByRole('button',{name:'Media library',exact:true}).click();await page.getByRole('button',{name:'Upload image',exact:true}).click();await page.getByLabel('Choose image file').setInputFiles('public/images/clinic.webp');await expect.poll(()=>page.getByAltText('Upload preview').evaluate((img:HTMLImageElement)=>img.naturalWidth>0)).toBe(true);await page.getByLabel('Image name').fill('Browser test image');await page.getByLabel('Description for screen readers').fill('Clinic reception for testing');await page.getByRole('button',{name:'Upload image',exact:true}).last().click();await expect(page.getByRole('dialog')).toHaveCount(0);await expect(page.getByText('Browser test image',{exact:true})).toBeVisible();
  const card=page.locator('.media-card').filter({hasText:'Browser test image'});const url=await card.locator('img').getAttribute('src');await page.getByRole('button',{name:'Edit Browser test image'}).click();await page.getByLabel('Choose image file').setInputFiles('public/images/care.webp');await page.getByLabel('Description for screen readers').fill('Replacement photograph');await page.getByRole('button',{name:'Save image',exact:true}).click();await expect(page.getByRole('dialog')).toHaveCount(0);await expect(card.locator('img')).toHaveAttribute('src',url!);await expect(card.locator('img')).toHaveAttribute('alt','Replacement photograph');
  await page.getByRole('button',{name:'Delete Browser test image'}).click();await page.getByRole('button',{name:'Delete image',exact:true}).click();await expect(page.getByText('Browser test image',{exact:true})).toHaveCount(0);
});
test('real HTTP requests enforce CSRF and guests cannot read admin data',async({request})=>{
  const missing=await request.post('/appointments',{form:{name:'Invalid'}});expect(missing.status()).toBe(419);const admin=await request.get('/admin/api/appointments',{headers:{Accept:'application/json'}});expect(admin.status()).toBe(401);
});
test('key public pages and admin dashboard meet automated WCAG AA checks',async({page})=>{
  test.setTimeout(90000);
  for(const path of ['/','/services','/services/general-dentistry','/about','/doctors','/articles','/branches','/gallery','/faq','/testimonials','/appointments','/contact','/login']){await page.goto(path);const results=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();expect(results.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>n.target)})),path).toEqual([]);}
  await login(page);const results=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();expect(results.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>n.target)}))).toEqual([]);
});

test('trust messages and verified statistics can be configured in the CMS',async({page})=>{
  await login(page);await page.goto('/admin#settings');await page.getByRole('button',{name:'Add trust message',exact:true}).click();const card=page.locator('.repeater-cards').last();await card.getByLabel('Title',{exact:true}).fill('A welcoming first visit');await card.getByLabel('Description',{exact:true}).fill('Bring your questions.');await page.getByRole('button',{name:'Add statistic',exact:true}).click();const stat=page.locator('.repeater-stats').last();await stat.getByLabel('Label',{exact:true}).fill('Verified locations');await stat.getByLabel('Value',{exact:true}).fill('2');await stat.getByLabel('Verified',{exact:true}).check();await page.getByRole('button',{name:'Save changes',exact:true}).first().click();await expect(page.getByRole('status')).toContainText('Changes saved.');
  await page.goto('/admin#sections');const row=page.locator('.section-editor-row').filter({has:page.getByLabel('statistics heading',{exact:true})});await row.getByRole('checkbox').check();await page.getByRole('button',{name:'Save homepage'}).click();await expect(page.getByRole('status')).toContainText('Homepage updated.');await page.goto('/');await expect(page.getByRole('heading',{name:'A welcoming first visit',exact:true})).toBeVisible();await expect(page.locator('.statistic-grid')).toContainText('Verified locations');
});

test('mobile administration, editor focus, and modal escape work',async({page})=>{
  await page.setViewportSize({width:390,height:844});await login(page);await page.getByRole('button',{name:'Open navigation',exact:true}).click();await expect(page.locator('.admin-sidebar')).toHaveClass(/open/);await page.getByRole('button',{name:'Services',exact:true}).click();await expect(page.locator('.admin-sidebar')).not.toHaveClass(/open/);await page.getByRole('button',{name:'Add item',exact:true}).click();await expect(page.getByRole('dialog')).toBeVisible();await page.getByRole('textbox',{name:'Rich text content'}).fill('Mobile content');await checkAccessibility(page);expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth)).toBe(true);await page.keyboard.press('Escape');await expect(page.getByRole('dialog')).toHaveCount(0);await expect(page.getByRole('button',{name:'Add item',exact:true})).toBeFocused();await page.goto('/admin#settings');await page.getByRole('button',{name:'Add statistic',exact:true}).waitFor();await checkAccessibility(page);
});

test('real HTTP login and patient submission limits reject repeated requests',async({request})=>{
  const response=await request.get('/login');const token=(await response.text()).match(/name="_token" value="([^"]+)"/)?.[1];expect(token).toBeTruthy();
  const statuses:number[]=[];for(let i=0;i<6;i++){const result=await request.post('/login',{form:{_token:token!,email:'throttle-test@example.test',password:'Invalid-test-password'},maxRedirects:0});statuses.push(result.status());}expect(statuses[5]).toBe(429);
  const patient:number[]=[];for(let i=0;i<6;i++){const result=await request.post('/appointments',{form:{_token:token!,name:'Rate limit test'},maxRedirects:0});patient.push(result.status());}expect(patient[5]).toBe(429);
});
