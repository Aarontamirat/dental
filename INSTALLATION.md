# Installation

## 1. Prepare the environment

Install PHP 8.3 or later and Composer 2. Enable GD with WebP, fileinfo, mbstring, DOM/XML, OpenSSL, PDO, and the database driver. Use MySQL 8.0+/MariaDB 10.6+ for deployment. SQLite is a convenient local default. Install Node 22.12+ and npm for asset compilation. `php -m`, `php --version`, and `composer check-platform-reqs` help confirm configuration.

On this Windows workspace, PHP is installed at `C:\php\php.exe`. A project-specific `.tools/php.ini` enables required extensions without changing the global PHP configuration. In PowerShell:

```powershell
$env:PHPRC = (Resolve-Path '.tools/php.ini').Path
php --version
php -m
```

The `.tools` configuration is local and excluded from releases. On another computer enable the extensions using that computer's PHP configuration.

## 2. Install locked dependencies

```sh
composer install --prefer-dist --no-interaction
npm ci
npm run build
```

Use the lockfiles. Do not run unrestricted dependency updates on a customer deployment. If download errors occur, correct network/proxy configuration and retry; do not disable TLS verification.

## 3. Configure a dedicated database

Copy `.env.example` to `.env` only if `.env` does not already exist. On Windows use `Copy-Item .env.example .env`; on Linux use `cp .env.example .env`. Set `APP_URL=http://127.0.0.1:8000` for this local preview.

For SQLite, create an empty `database/database.sqlite` file and set `DB_CONNECTION=sqlite`. Laravel resolves the default path. For MySQL create a dedicated empty database and user, then set:

```dotenv
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_dedicated_database
DB_USERNAME=your_dedicated_user
DB_PASSWORD="your-private-value"
```

Use `utf8mb4`. Do not point test or installation commands at an unrelated database. Database credentials belong only in private environment configuration.

## 4. Install the foundation and administrator

```sh
php artisan key:generate
php artisan clinic:install
```

Generate a key once per new installation. Preserve it for upgrades and restores. `clinic:install` runs migrations, adds the supported homepage sections/navigation without overwriting edits, and prompts for the first administrator if none exists. It does not erase existing tables.

For a demonstration installation use `php artisan clinic:install --demo`. This adds clearly identified demonstration records. Alternatively run `php artisan db:seed --class=DemoSeeder`. Default `db:seed` installs only the structural foundation. No seeder creates a production password.

The administrator password must contain at least 12 characters, mixed case, a number, and a symbol. Further administrators can be created with `php artisan clinic:admin`, or through People & permissions. Recover an existing account using `php artisan clinic:reset-password admin@example.com`, with a hidden password prompt.

## 5. Run and configure

```sh
php artisan serve --host=127.0.0.1 --port=8000
php artisan clinic:check
```

Open `/login` and configure the clinic using [CLINIC_CUSTOMIZATION_GUIDE.md](CLINIC_CUSTOMIZATION_GUIDE.md). For frontend development run `npm run dev` in another terminal; stop it and run `npm run build` before packaging. Production uses the compiled manifest and must not include `public/hot`.

Fresh installations need real branches, services, published privacy/payment pages, and branding before public launch. Static demo imagery and default marketing text are starting material. The CMS supports every homepage section immediately; empty collections display graceful empty states.

## 6. Verification

```sh
npm run typecheck
npm run lint
php vendor/bin/pint --test
php artisan test
npm run test:e2e
```

The default backend tests use in-memory SQLite. Browser tests create a new isolated SQLite database, a random account, and a loopback server on port 8011. See [TESTING.md](TESTING.md). Never run `migrate:fresh`, database resets, or test suites against customer data.
