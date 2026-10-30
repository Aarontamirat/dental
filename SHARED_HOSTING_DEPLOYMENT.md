# cPanel shared-hosting deployment

## Supported account

Use Linux cPanel with Apache/LiteSpeed, PHP **8.3+**, MySQL **8.0+** or MariaDB **10.6+**, SSL, and rewrite support. Enable GD with WebP, fileinfo, mbstring, DOM/XML, OpenSSL, PDO/pdo_mysql, ctype, filter, session, and tokenizer. Allow PHP memory of at least **256 MB** and upload/post limits of at least **8 MB**. CLI and web PHP must have compatible versions/extensions.

No Node, Docker, Redis, VPS, persistent workers, or paid service is required. The preferred layout puts the application at `/home/ACCOUNT/clinic`, with `/home/ACCOUNT/clinic/public` as the domain document root. Only the public directory may be web-accessible.

## 1. Build a clean release locally

Run the checks in [TESTING.md](TESTING.md), then:

```sh
npm ci
npm run build
python tools/package-release.py
```

The tool creates a ZIP and SHA-256 checksum in ignored `.tools/releases`. It installs locked production PHP dependencies in a temporary staging directory without modifying your development vendor directory. It includes compiled assets, views, migrations, CLI commands, empty writable directories, and documentation. It excludes `.env`, databases, uploads, credentials, logs, caches, tests, Node dependencies, and development tools. Python and Composer are build tools only.

Composer's `--no-scripts` build avoids running setup against a developer database. Package discovery runs on the host. The host still needs the required PHP extensions. Do not upload your development checkout wholesale or a development vendor directory. Keep the ZIP and extracted source outside the public root.

## 2. Prepare cPanel

1. In MultiPHP Manager/Select PHP Version select a maintained PHP 8.3+ patch and enable extensions. Confirm the separate CLI binary with the provider.
2. Create a new MySQL database and dedicated user through Database Wizard. Use full cPanel-prefixed names, `utf8mb4`, and privileges only on that clinic's database. Migrations need schema privileges.
3. Point the domain DNS to the account. Enable AutoSSL or an equivalent certificate and HTTPS redirection.
4. Extract the release to `/home/ACCOUNT/clinic`. Set the domain document root to `/home/ACCOUNT/clinic/public`. Show hidden files and preserve `public/.htaccess`.

Never expose `app`, `vendor`, `storage`, `bootstrap`, or `.env` publicly. Do not place passwords in shell arguments, installers, documentation, or ticket screenshots.

## 3. Private environment and permissions

Create `/home/ACCOUNT/clinic/.env` from `.env.example`. Edit it privately in File Manager/SFTP:

```dotenv
APP_NAME="Your Clinic"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://clinic.example
APP_KEY=
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ACCOUNT_clinic
DB_USERNAME=ACCOUNT_clinicuser
DB_PASSWORD="REPLACE_PRIVATELY"
SESSION_DRIVER=database
SESSION_LIFETIME=60
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=single
LOG_LEVEL=warning
MAIL_MAILER=log
```

Replace every placeholder. Quote environment values containing special characters using dotenv syntax. Generate `APP_KEY` once during initial setup and preserve it on upgrades/restores. Website notifications stay disabled until real mail transport is configured in both the environment and CMS. Leave `APP_PUBLIC_PATH` unset for the preferred layout.

Use account-owned directories/files, initially directories `755`, files `644`, and `.env`/private credential files `600` where supported. Only `storage` and `bootstrap/cache` need write access. Follow the provider's PHP ownership rules; never use `777`.

## 4. Install with SSH or cPanel Terminal

Replace `php` with the provider's PHP 8.3+ CLI binary if needed:

```sh
cd /home/ACCOUNT/clinic
php artisan package:discover
php artisan key:generate --force
php artisan clinic:install
```

Installation applies migrations, initializes homepage sections/navigation, and prompts privately for a strong administrator password. Do not use `--demo` for a live clinic. If Composer is available and the release has no vendor directory, use `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction` in the private application directory. The prebuilt release needs no host Composer.

Sign in at `/login`, add real clinic branding/content, publish the reviewed privacy page, and disable demonstration mode. Then:

```sh
php artisan clinic:check
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Generate caches on the host after environment/database setup. Never ship cached developer configuration. After `.env` changes clear config, verify, and cache again. Database branding changes apply immediately without an asset rebuild.

## 5. No host SSH or Composer

Upload the prebuilt release. cPanel Cron Jobs can run private CLI commands. The common binary `/opt/cpanel/ea-php83/root/usr/bin/php` is an example; confirm your provider's path. Run each step separately after the previous one succeeds. Use a temporary every-minute cron, inspect its private output, and remove it immediately after success. Do not leave installation/admin jobs scheduled.

Keep the setup log outside the web root:

```sh
/opt/cpanel/ea-php83/root/usr/bin/php /home/ACCOUNT/clinic/artisan package:discover > /home/ACCOUNT/clinic-setup.log 2>&1
```

Use the same form for these commands, in order:

```text
key:generate --force
migrate --force --no-interaction
db:seed --class=ClinicFoundationSeeder --force --no-interaction
```

Generate the key only once for a new installation. Do not run interactive `clinic:install` through cron. For the first administrator place a private file `/home/ACCOUNT/clinic-admin.json` outside **every** public document root, with permissions `600`:

```json
{
  "name": "Clinic Administrator",
  "email": "admin@your-real-clinic.example",
  "password": "REPLACE_WITH_YOUR_UNIQUE_STRONG_PASSWORD"
}
```

This is a format example, not usable credentials. Supply a unique password of at least 12 characters, mixed case, number, and symbol. Run this one-time cron:

```sh
/opt/cpanel/ea-php83/root/usr/bin/php /home/ACCOUNT/clinic/artisan clinic:admin-file /home/ACCOUNT/clinic-admin.json > /home/ACCOUNT/clinic-setup.log 2>&1
```

The command validates/creates the account and deletes the credential file. Verify deletion manually, remove the cron, and securely remove setup artifacts after review. Files under the configured public root are rejected. After content setup run `clinic:check`, `config:cache`, `route:cache`, and `view:cache` through temporary cron commands. Keep only approved operational jobs.

If a provider has neither Terminal/SSH nor PHP CLI cron, ask them to run these private commands or use a plan supporting PHP CLI cron. There is deliberately no public installer or migration endpoint.

## 6. Fixed public_html fallback

If the primary domain document root cannot change, keep the private application at `/home/ACCOUNT/clinic`. Copy only the **contents** of its `public` directory to `/home/ACCOUNT/public_html`, including `.htaccess`, `build`, fonts, and images. Copy no other application directory there.

Replace `public_html/index.php` with this front controller, using your actual private path:

```php
<?php
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
$base = '/home/ACCOUNT/clinic';
if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}
require $base.'/vendor/autoload.php';
/** @var Application $app */
$app = require_once $base.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);
$app->handleRequest(Request::capture());
```

Add `APP_PUBLIC_PATH=/home/ACCOUNT/public_html` to the **private** `.env`. CLI commands then use the actual assets and public-root restrictions. Clear/rebuild config cache. Media is served through `/media/{id}`; no storage symlink is needed. Never expose private storage.

For upgrades update the public assets and private code together, preserve this customized front controller, and regenerate caches. Do not merge a release over an unrelated site without an arranged backup/migration.

## 7. Configure email

Use a clinic-domain mailbox or the host's approved SMTP account. Typical STARTTLS configuration:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=mail.your-clinic.example
MAIL_PORT=587
MAIL_USERNAME=website@your-clinic.example
MAIL_PASSWORD="REPLACE_PRIVATELY"
MAIL_FROM_ADDRESS=website@your-clinic.example
MAIL_FROM_NAME="Your Clinic"
MAIL_TIMEOUT=10
```

For implicit TLS/465 use `MAIL_SCHEME=smtps` if instructed by the provider. Configure SPF/DKIM and appropriate DMARC. Rebuild config cache. In Branding & settings set Clinic notification email and enable email; branch appointment email overrides the main request recipient.

Test a request, a status update to a consenting test patient, and password recovery. Verify actual inbox/spam-folder delivery. Failed mail does not discard requests; staff must check the dashboard and follow up by telephone. Real SMTP delivery must be verified for each host.

## 8. Launch and operations

- Run `clinic:check` in production, including a real published privacy notice and a published booking branch when requests are enabled.
- Check public pages, details, booking, contact, login, sitemap, robots, and both languages.
- Submit a consented test request; verify its reference, Pending state, update/history, and email.
- Test mobile/keyboard navigation, uploads/replacement, branding, maps, phone, and WhatsApp.
- Request `/.env`, `/vendor/autoload.php`, and `/storage/logs/laravel.log`; none may expose private files. Disable directory listing.
- Confirm HTTPS cookies, canonical domain, and removal of demo biographies/reviews or stock portraits implying actual staff.
- Establish [backups and a restoration drill](BACKUP_AND_RESTORATION.md).

Core operation needs no scheduled worker. Retention cleanup is an optional explicit operation; preview counts before approving deletion. Monitor disk capacity, mail failures, PHP updates, backups, and the patient inbox.
