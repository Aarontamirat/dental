# Dental clinic website platform

A reusable, independently deployed dental clinic website for Ethiopia. Laravel serves the public website and patient forms; React powers the administration workspace. Each clinic receives its own database, branding, content, and media. Production runs on PHP shared hosting without Node, Docker, Redis, queue workers, or a paid service.

## Included

- Responsive public website: services, professionals, branches, articles, FAQs, gallery, testimonials, and custom pages.
- CMS with safe rich text, publication controls, categories, relationships, navigation, search, and pagination.
- Configurable branding, fonts, layout presets, homepage sections, verified statistics, and optional modules.
- English and Amharic interfaces, reviewed content translations, local Ethiopic fonts, ETB prices, and Addis Ababa time.
- Appointment requests, contact messages, role-based administration, status history, internal notes, and CSV export.
- Local image library with WebP optimization, replacement, alt text, and private originals.
- Session authentication, recovery, CSRF protection, input validation, throttling, consent, SEO, and deployment checks.

Appointments require clinic confirmation. This product stores inquiry details and visit preferences; it is not a medical records or real-time scheduling system.

## Requirements

PHP **8.3+**, MySQL **8.0+** or MariaDB **10.6+**, and Apache/LiteSpeed rewrite support. PHP needs GD with WebP, fileinfo, mbstring, DOM/XML, ctype, filter, OpenSSL, PDO/pdo_mysql, session, and tokenizer. Use a maintained PHP patch release; 8.4 or 8.5 is recommended when offered. Composer 2 and Node **22.12+** are build tools only. Local development/tests use SQLite with pdo_sqlite.

## Start locally

Use a new database dedicated to this project. See [INSTALLATION.md](INSTALLATION.md) for Windows configuration and exact setup.

```sh
composer install
cp .env.example .env
php artisan key:generate
npm ci
npm run build
php artisan clinic:install --demo
php artisan serve --host=127.0.0.1 --port=8000
```

The install command creates schema and prompts for an administrator with a hidden password. Open `http://127.0.0.1:8000`, then `/login` for administration. There is no shared default password. In this prepared workspace, the existing demo credentials are stored privately in ignored `.tools/local-access.json`; do not distribute that file.

## Guides

| Guide                                                          | Purpose                                         |
| -------------------------------------------------------------- | ----------------------------------------------- |
| [ARCHITECTURE.md](ARCHITECTURE.md)                             | Architecture, data model, extension points      |
| [INSTALLATION.md](INSTALLATION.md)                             | Development and clean setup                     |
| [SHARED_HOSTING_DEPLOYMENT.md](SHARED_HOSTING_DEPLOYMENT.md)   | cPanel, release builds, and no-SSH installation |
| [ADMIN_USER_GUIDE.md](ADMIN_USER_GUIDE.md)                     | Daily clinic operations                         |
| [CLINIC_CUSTOMIZATION_GUIDE.md](CLINIC_CUSTOMIZATION_GUIDE.md) | Branding, onboarding, and customer handover     |
| [BACKUP_AND_RESTORATION.md](BACKUP_AND_RESTORATION.md)         | Backups, upgrades, and recovery                 |
| [SECURITY.md](SECURITY.md)                                     | Controls, privacy, and operating boundaries     |
| [TESTING.md](TESTING.md)                                       | Executed checks and repeatable verification     |
| [ASSET_LICENSES.md](ASSET_LICENSES.md)                         | Photography, font licenses, and replacement     |

The product is built and tested locally. Hosting deployment and real SMTP delivery must be verified on each customer's account. Demonstration photography, biographies, reviews, and clinical text are explicitly labelled and must be replaced or hidden before launch.

The project is already installed and built.

1. Open a PowerShell terminal in your IDE and run:
   cd 'C:\Users\AaronT.LUCY\Desktop\Projects\Websites\DentalClinics\dental3'
   $env:PHPRC = (Resolve-Path '.tools/php.ini').Path
   php artisan serve --host=127.0.0.1 --port=8000
   Keep this terminal running. Press Ctrl+C to stop the server.
2. Open:
    - Website: http://localhost:8000
    - Admin login: http://localhost:8000/login
      Your local login email and password are stored in [local-access.json](C:/Users/AaronT.LUCY/Desktop/Projects/Websites/DentalClinics/dental3/.tools/local-access.json).
3. After signing in, use:
    - Appearance studio — themes, colors, fonts, layouts, and previews. Click Publish changes to apply.
    - Branding & settings — clinic name, logo, contact details, and languages.
    - Homepage sections — section text, visibility, and ordering.
    - Services, Doctors & staff, Branches — clinic content.
    - Appointment requests — manage bookings.
      Normal admin edits appear immediately after saving. After editing frontend source files, run npm run build in another terminal and refresh the browser.
