import { test, expect, type Page } from '@playwright/test';
import { readFileSync, mkdirSync } from 'node:fs';
import AxeBuilder from '@axe-core/playwright';

const publicPaths = ['/', '/about', '/services', '/services/general-dentistry', '/doctors', '/doctors/demo-general-dentist', '/branches', '/branches/addis-ababa-demo', '/appointments', '/articles', '/articles/small-habits-healthier-smiles', '/gallery', '/testimonials', '/faq', '/contact', '/pages/privacy', '/pages/terms', '/?lang=am'];

async function login(page: Page) {
  const access = JSON.parse(readFileSync('.tools/e2e-access.json', 'utf8'));
  await page.goto('/login');
  await page.getByLabel('Email address').fill(access.email);
  await page.getByLabel('Password', { exact: true }).fill(access.password);
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await page.waitForURL('**/admin');
  return await page.locator('meta[name="csrf-token"]').getAttribute('content') ?? '';
}

async function snapshot(page: Page, name: string) {
  mkdirSync('.tools/previews', { recursive: true });
  await page.evaluate(async () => {
    await document.fonts.ready;
    await Promise.all([...document.images].map(image => { image.loading = 'eager'; return image.decode().catch(() => undefined); }));
    window.scrollTo({ top: 0, behavior: 'instant' });
  });
  await page.screenshot({ path: `.tools/previews/phase2-${name}.png`, fullPage: true, animations: 'disabled' });
}

async function accessibility(page: Page) {
  const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze();
  return result.violations.map(violation => ({ id: violation.id, nodes: violation.nodes.map(node => ({ target: node.target, summary: node.failureSummary })) }));
}

test('studio previews, recovers drafts, publishes, discards and restores defaults', async ({ page, browser }) => {
  test.setTimeout(90000);
  const csrf = await login(page);
  const original = await (await page.request.get('/admin/api/appearance')).json();
  try {
    await page.goto('/admin#appearance');
    await expect(page.getByRole('heading', { name: 'Appearance studio', exact: true })).toBeVisible();
    const frame = page.frameLocator('iframe[title="Private appearance preview"]');
    await expect(frame.locator('h1')).toBeVisible();
    await snapshot(page, 'studio-desktop');
    for (const category of ['Theme presets', 'Colors', 'Typography', 'Layout & spacing', 'Buttons', 'Cards & inputs', 'Navigation', 'Footer', 'Homepage', 'Animations', 'Backup & restore']) {
      await page.getByRole('navigation', { name: 'Appearance categories' }).getByRole('button', { name: category, exact: true }).click();
      await expect(page.getByRole('heading', { level: 2, name: category, exact: true })).toBeVisible();
    }
    await page.getByRole('button', { name: 'Typography', exact: true }).click();
    await page.getByLabel('Base font size').fill('20');
    await expect(frame.locator('body')).toHaveCSS('font-size', '19.792px');
    const visitor = await browser.newPage();
    await visitor.goto('/');
    expect(await visitor.locator('style#appearance-tokens').textContent()).not.toContain('--typography-body-size:20px');
    await visitor.close();
    await page.getByRole('button', { name: 'Mobile preview', exact: true }).click();
    await expect.poll(async () => parseFloat(await frame.locator('body').evaluate(element => getComputedStyle(element).fontSize))).toBeCloseTo(18.54, 1);
    await page.getByLabel('Preview page').selectOption('/appointments');
    await expect(frame.locator('.submit-button')).toBeDisabled();
    await page.getByLabel('Preview page').selectOption('/services');
    await frame.locator('.service-card').first().click();
    await expect(frame.locator('body')).toHaveAttribute('data-kind', 'services');
    await page.getByLabel('Base font size').fill('19');
    await expect(page.getByLabel('Preview page')).toHaveValue('/services/general-dentistry');
    await page.getByLabel('Base font size').fill('20');
    await page.getByRole('navigation', { name: 'Clinic administration' }).getByRole('button', { name: 'Overview', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Hello, E2E.' })).toBeVisible();
    await page.goto('/admin#appearance');
    await expect(page.getByRole('button', { name: 'Recover draft', exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Recover draft', exact: true }).click();
    await page.getByRole('button', { name: 'Publish changes', exact: true }).click();
    await expect(page.locator('.appearance-state')).toContainText('Published');
    await page.getByRole('button', { name: 'Typography', exact: true }).click();
    await page.getByLabel('Base font size').fill('21');
    await page.getByRole('button', { name: 'Discard', exact: true }).click();
    await page.getByRole('button', { name: 'Discard draft', exact: true }).click();
    await expect(page.getByLabel('Base font size')).toHaveValue('20');
    await page.getByRole('button', { name: 'Backup & restore', exact: true }).click();
    await page.getByRole('button', { name: 'Restore design defaults', exact: true }).click();
    await page.getByRole('button', { name: 'Restore defaults to draft', exact: true }).click();
    await page.getByRole('button', { name: 'Typography', exact: true }).click();
    await expect(page.getByLabel('Base font size')).toHaveValue('18');
    await page.getByRole('button', { name: 'Colors', exact: true }).click();
    await page.getByLabel('Text', { exact: true }).fill('#ffffff');
    await expect(page.locator('.appearance-warnings')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Publish changes', exact: true })).toBeDisabled();
    await page.getByRole('button', { name: 'Reset Text color', exact: true }).click();
    await page.getByRole('button', { name: 'Backup & restore', exact: true }).click();
    const download = page.waitForEvent('download');
    await page.getByRole('button', { name: 'Export appearance', exact: true }).click();
    expect((await download).suggestedFilename()).toBe('clinic-appearance.json');
    const importedConfiguration = structuredClone(original.defaults);
    importedConfiguration.header.sticky = '0';
    await page.locator('input[type=file]').setInputFiles({ name: 'appearance.json', mimeType: 'application/json', buffer: Buffer.from(JSON.stringify({ configuration: importedConfiguration, homepage: original.homepage })) });
    await expect(page.locator('.admin-toast')).toContainText('Backup imported');
    await page.getByRole('navigation', { name: 'Appearance categories' }).getByRole('button', { name: 'Navigation', exact: true }).click();
    await expect(page.getByRole('checkbox', { name: 'Sticky header', exact: true })).not.toBeChecked();
    await page.getByRole('button', { name: 'Footer', exact: true }).click();
    await page.getByLabel('Footer layout').selectOption('minimal');
    await expect(page.getByRole('checkbox', { name: 'Show contact details' })).toHaveCount(0);
    await page.getByRole('button', { name: 'Homepage', exact: true }).click();
    const services = page.locator('.appearance-sections>details').filter({ has: page.locator('summary>strong', { hasText: 'Services' }) });
    await services.locator('summary').click();
    await services.getByRole('button', { name: 'Move services up' }).click();
    await services.getByRole('checkbox').uncheck();
    await page.getByLabel('Preview page').selectOption('/');
    await expect(frame.locator('[data-section=services]')).toHaveCount(0);
    await page.setViewportSize({ width: 390, height: 844 });
    await snapshot(page, 'studio-mobile');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    expect(await accessibility(page)).toEqual([]);
  } finally {
    const current = await (await page.request.get('/admin/api/appearance')).json();
    await page.request.put('/admin/api/appearance', { headers: { 'X-CSRF-TOKEN': csrf }, data: { configuration: original.configuration, homepage: original.homepage, revision: current.revision } });
  }
});

test('all six presets render in light and dark with readable contrast', async ({ page }) => {
  test.setTimeout(180000);
  const csrf = await login(page);
  const data = await (await page.request.get('/admin/api/appearance')).json();
  for (const [key, preset] of Object.entries(data.presets)) {
    for (const mode of ['light', 'dark']) {
      const configuration = structuredClone((preset as { configuration: typeof data.configuration }).configuration);
      configuration.mode = mode;
      const result = await page.request.post('/admin/api/appearance/preview', { headers: { 'X-CSRF-TOKEN': csrf }, data: { configuration } });
      expect(result.ok()).toBe(true);
      const token = (await result.json()).token;
      await page.setViewportSize({ width: 1440, height: 1000 });
      await page.goto(`/?appearance_preview=${token}`);
      await snapshot(page, `${key}-${mode}`);
      expect.soft(await accessibility(page), `${key} ${mode}`).toEqual([]);
      for (const width of [320, 768, 1440]) {
        await page.setViewportSize({ width, height: 900 });
        expect.soft(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${key} ${mode} ${width}px`).toBe(true);
      }
    }
  }
});

for (const width of [320, 390, 768, 1024, 1440]) {
  test(`all public pages remain readable without overflow at ${width}px`, async ({ page }) => {
    test.setTimeout(120000);
    await page.setViewportSize({ width, height: 900 });
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    for (const path of publicPaths) {
      expect((await page.goto(path))?.status(), path).toBe(200);
      await page.evaluate(() => document.fonts.ready);
      expect.soft(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), path).toBe(true);
      expect.soft(await page.locator('h1').count(), path).toBe(1);
      if (width === 390 || width === 1440) await snapshot(page, `${path.replace(/[^a-z]+/gi, '-') || 'home'}-${width}`);
      if (width === 390) expect.soft(await accessibility(page), path).toEqual([]);
    }
    expect(errors).toEqual([]);
  });
}

test('layout variants, system palette and reduced motion affect the rendered site', async ({ page }) => {
  const csrf = await login(page);
  const data = await (await page.request.get('/admin/api/appearance')).json();
  const configuration = structuredClone(data.defaults);
  configuration.mode = 'system';
  configuration.header.style = 'centered';
  configuration.header.mobile_style = 'sheet';
  configuration.footer.style = 'minimal';
  configuration.sections.services.layout = 'rows';
  configuration.sections.technology.image = 'right';
  configuration.sections.testimonials.layout = 'quotes';
  configuration.motion.page_transitions = false;
  const response = await page.request.post('/admin/api/appearance/preview', { headers: { 'X-CSRF-TOKEN': csrf }, data: { configuration } });
  const token = (await response.json()).token;
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.emulateMedia({ colorScheme: 'dark', reducedMotion: 'reduce' });
  await page.goto(`/?appearance_preview=${token}`);
  await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(16, 29, 27)');
  await expect(page.locator('html')).toHaveCSS('view-transition-name', 'none');
  await expect(page.locator('.technology-image')).toHaveCSS('order', '2');
  await expect(page.locator('.footer-column').first()).toBeHidden();
  await expect(page.locator('.testimonial-cards')).toBeVisible();
  await page.locator('.service-card').first().hover();
  await expect(page.locator('.service-card').first()).toHaveCSS('transform', 'none');
  expect(await accessibility(page)).toEqual([]);
  await page.emulateMedia({ colorScheme: 'light', reducedMotion: 'no-preference' });
  await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(252, 251, 248)');
  await page.setViewportSize({ width: 390, height: 844 });
  await page.evaluate(() => scrollTo({ top: 0, behavior: 'instant' }));
  await page.locator('.menu-toggle').click();
  await expect(page.locator('#mobile-menu')).toBeVisible();
  const bounds = await page.locator('#mobile-menu').boundingBox();
  expect(bounds?.y).toBeGreaterThan(78);
  await page.keyboard.press('Escape');
  await expect(page.locator('#mobile-menu')).toBeHidden();
  await expect(page.locator('.menu-toggle')).toBeFocused();
});

test('appointment errors retain input, link to fields and announce closed days', async ({ page }) => {
  await page.goto('/appointments');
  await page.getByLabel('Full name').fill('Validation patient');
  await page.getByLabel('Phone number').fill('invalid');
  const day = new Date(); day.setDate(day.getDate() + 8); while (day.getDay() !== 1) day.setDate(day.getDate() + 1);
  await page.locator('#preferred_date').fill(day.toISOString().slice(0, 10));
  await page.locator('#preferred_time').fill('10:00');
  await page.getByRole('checkbox').check();
  await page.waitForTimeout(2100);
  await page.locator('.submit-button').click();
  await expect(page.locator('#form-errors')).toBeFocused();
  await expect(page.locator('#phone')).toHaveAttribute('aria-invalid', 'true');
  await expect(page.locator('#phone')).toHaveValue('invalid');
  await page.locator('#error-phone a').click();
  await expect(page.locator('#phone')).toBeFocused();
  day.setDate(day.getDate() + 6);
  await page.locator('#preferred_date').fill(day.toISOString().slice(0, 10));
  await expect(page.locator('#branch-availability')).toContainText('closed');
  expect(await accessibility(page)).toEqual([]);
});

test('public information and navigation remain available without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } });
  const page = await context.newPage();
  await page.goto('/');
  await expect(page.locator('h1')).toBeVisible();
  await expect(page.locator('.nojs-nav')).toBeVisible();
  await page.goto('/appointments');
  await expect(page.locator('fieldset')).toHaveCount(3);
  await expect(page.locator('.submit-button')).toBeEnabled();
  await context.close();
});

test('narrow sections accommodate large typography and dense card preferences', async ({ page }) => {
  const csrf = await login(page);
  const data = await (await page.request.get('/admin/api/appearance')).json();
  const configuration = structuredClone(data.defaults);
  configuration.layout.narrow_width = 640;
  configuration.layout.content_width = 1000;
  configuration.layout.density = 'spacious';
  configuration.cards.columns = '4';
  configuration.cards.padding = 44;
  configuration.typography.h3 = 34;
  configuration.typography.paragraph_size = 24;
  configuration.sections.services.layout = 'rows';
  for (const section of Object.values(configuration.sections)) (section as { width: string }).width = 'narrow';
  const response = await page.request.post('/admin/api/appearance/preview', { headers: { 'X-CSRF-TOKEN': csrf }, data: { configuration } });
  expect(response.ok()).toBe(true);
  const token = (await response.json()).token;
  for (const width of [1440, 1024, 768, 390, 320]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto(`/?appearance_preview=${token}`);
    await page.evaluate(() => document.fonts.ready);
    const overflowing = await page.locator('main :is(.container:not(.hero-grid),.service-card,.doctor-card,.article-card,.hero-copy,.technology-card,.appointment-banner,.trust-inner>div)').evaluateAll(elements => elements.filter(element => element.scrollWidth > element.clientWidth + 1).map(element => element.className));
    expect.soft(overflowing, `${width}px containers must contain their text`).toEqual([]);
    expect.soft(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${width}px page`).toBe(true);
  }
});

test('card interaction none disables image motion and secondary color styles the icon tiles', async ({ page }) => {
  const csrf = await login(page);
  const data = await (await page.request.get('/admin/api/appearance')).json();
  const configuration = structuredClone(data.defaults);
  configuration.cards.hover = 'none';
  configuration.colors.light.secondary = '#713d59';
  configuration.buttons.weight = '700';
  configuration.buttons.icon_size = 24;
  configuration.buttons.icon_position = 'start';
  const response = await page.request.post('/admin/api/appearance/preview', { headers: { 'X-CSRF-TOKEN': csrf }, data: { configuration } });
  expect(response.ok()).toBe(true);
  await page.goto(`/?appearance_preview=${(await response.json()).token}`);
  await page.locator('.doctor-card').first().hover();
  await expect(page.locator('.doctor-card img').first()).toHaveCSS('transform', 'none');
  await expect(page.locator('.service-icon').first()).toHaveCSS('background-color', 'rgb(113, 61, 89)');
  await expect(page.locator('.text-link').first()).toHaveCSS('font-weight', '700');
  await expect(page.locator('.text-link>svg').first()).toHaveCSS('width', '24px');
  await expect(page.locator('.text-link>svg').first()).toHaveCSS('order', '-1');
});
