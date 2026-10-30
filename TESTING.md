# Verification record

## Executed environment

Local verification on Windows 10, **PHP 8.5.10**, Composer **2.10.3**, Node **24.21.0**, and Chromium through Playwright. Dependencies are locked; the installed framework is Laravel **13.39.0**. Composer resolves production dependencies with PHP **8.3.0** as the minimum platform. Actual PHP 8.3 execution and a live cPanel/Apache/LiteSpeed deployment have not occurred here.

The default tests use in-memory SQLite. MySQL verification used a newly initialized, loopback-only **MySQL 8.0.44** instance on port 3317 and the dedicated `dental3_qa_only` database. It did not use the existing XAMPP database service or demonstration database. MySQL/MariaDB host acceptance remains part of each customer installation; MariaDB 10.6 has not been separately executed locally.

## Results

| Check | Recorded result |
| --- | --- |
| Backend on SQLite | **78 tests, 552 assertions passed** in the Phase 2 requirements recheck |
| Backend on isolated MySQL | Historical Phase 1 run: **52 tests, 416 assertions passed**; not rerun for Phase 2 |
| Real-browser journeys | Current Phase 2 results are recorded in FRONTEND_AUDIT.md; historical Phase 1 run: 12 passed |
| Local mobile Lighthouse | Historical Phase 1 run: **Performance 95, Accessibility 100, Best practices 100, SEO 100**; not rerun for Phase 2 |
| TypeScript | `npm run typecheck` passed |
| JavaScript lint | `npm run lint` passed |
| PHP formatting | Pint passed for changed PHP files; `--dirty` is unavailable because this workspace has no Git metadata |
| Production assets | `npm run build` passed |
| Composer manifest/lock | `composer validate --strict` passed |
| JavaScript dependencies | `npm audit --audit-level=low`: **0 vulnerabilities** |
| PHP dependency audit | Earlier audit reported no advisories/abandoned packages; final recheck could not reach Packagist's security endpoint |
| Migrations and Blade | Migration status, fresh isolated migrations, and `view:cache` passed |
| Local readiness | `clinic:check` passed for the local demonstration |
| Production-dependency release | Clean ZIP with production dependencies and responsive demo assets |
| Release smoke | Private CLI setup/caches and separate public-root HTTP verification passed |

Final PHP audit network error: Packagist's security-advisories endpoint timed out. Re-run the audit when connectivity is available before a customer release; an unreachable audit is not a clean audit result.

The repository's Laravel Boost development setup was also attempted. Packagist/GitHub downloads failed; verified dependency manifests were restored and Composer install/validation completed. Boost is optional development tooling and is not a product runtime dependency.

## Coverage

Backend checks cover authentication/recovery, active accounts, role boundaries, privilege escalation restrictions, CRUD across all modules, relationship integrity, safe HTML/URLs/uploads, image replacement/reference deletion, validation, consent, request references, date/time/branch availability, statuses/history, stale updates, rescheduling, notification success/failure, CSV escaping, metadata, feature flags, publication/demo rules, reviewed translations, homepage synchronization, trust/statistics, and fresh idempotent foundation setup.

Browser checks use actual HTTP/CSRF sessions and compiled production assets. They cover public routes/images/JSON-LD, widths 320/390/768, navigation/Escape/language, appointment submission and clinic confirmation, contact submission/resolution, rich editor create/edit/delete with public rendering, branding/homepage controls, upload preview/replacement/deletion, unauthorized API restrictions, real login/patient throttling, trust/statistics UI, and mobile administration/dialog focus restoration.

Automated Axe checks reported no WCAG A/AA violations on the tested home, services/detail, about, doctors, articles, branches, gallery, FAQs, testimonials, booking, contact, login, dashboard, mobile editor dialog, and branding/settings views. Checks include WCAG 2, 2.1, and 2.2 AA tags. This is automated coverage, not an accessibility certification or a full assistive-technology audit.

The release smoke tool extracts a clean archive into temporary private/public directories, creates a new SQLite database/random account, exercises package discovery, key creation, migrations/demo seeding, private account-file deletion, config/route/view caching, and readiness. It confirms public pages/assets resolve, credential setup rejects the configured public directory, and private environment/vendor/log URLs return denial or not-found responses. All smoke data is isolated and removed afterward.

## Repeat the checks

From the developer checkout with dependencies/assets installed:

```sh
npm run typecheck
npm run lint
php vendor/bin/pint --dirty --format agent
php artisan test
npm run build
npm run test:e2e
composer validate --strict
composer audit
npm audit --audit-level=low
python tools/package-release.py
python tools/release-smoke.py
```

On this Windows installation set `$env:PHPRC = (Resolve-Path '.tools/php.ini').Path` first so Artisan's test subprocess inherits the SQLite extensions. With no Git metadata, pass the changed PHP file paths to Pint instead of `--dirty`. A temporary streams-only PHP configuration was used for Composer where the local curl transport failed; TLS verification was preserved.

Browser tests create a fresh database/random account under ignored `.tools`, start port **8011**, and never reuse the local preview database. Install a Playwright Chromium browser when none is available: `npx playwright install chromium`. `PLAYWRIGHT_CHROMIUM_EXECUTABLE` can point to an existing compatible Chromium executable. Authenticated tests share a legitimate session after one real login so they do not weaken production login limits. Guest requests stay separate.

The isolated MySQL configuration `.tools/phpunit.mysql.xml` is machine-specific and intentionally ignored. To repeat on another computer, create a dedicated empty QA database/user, copy `phpunit.xml`, and override only test DB connection settings. `RefreshDatabase` drops/rebuilds test schema: **never point it at customer or unrelated databases**. Use production backups restored to isolated staging for upgrade tests, with SMTP disabled.

## Performance and external acceptance

The production public JS bundle is about **4.65 KB** before compression; administration is about **307.39 KB**, with a separately loaded **403.70 KB** rich editor. Public CSS is about **96.75 KB** and admin CSS about **40.84 KB** before compression. Fonts and photographs are local; public images use lazy loading except primary content, explicit dimensions, and WebP. Bundled hero/about images have 480/960-pixel variants and responsive source selection. Query relationships are eager loaded where appropriate and content lists paginate. Apache/LiteSpeed rules enable optional text compression and static asset caching.

Local mobile Lighthouse improved from **85/95/100/100** to **95/100/100/100** after responsive images, smaller English-page font loading, accessible branding names, and larger language targets. The final run reported LCP approximately **2.5 seconds**, CLS **0.002**, and total blocking time **180 ms**. Reports are in ignored `.tools/lighthouse-mobile*.json`; the run used the local PHP server while other QA activity was occurring. These are laboratory observations, not production Core Web Vitals or guaranteed customer scores. Network, hosting, image choices, and real clinic content affect results. Verify against the actual deployment.

Local screenshots in `.tools/previews` show desktop/mobile public/admin views after images/fonts load; `node tools/preview.mjs` regenerates them using the private local demo account.

Real SMTP delivery, actual HTTPS/proxy behavior, host filesystem permissions, cPanel cron/Apache rewrite configuration, human clinical/language review, a hosting backup/restore drill, manual screen-reader review, and professional penetration testing are not claimed as completed. The hosting/customization guides provide the customer acceptance procedure.
