# Architecture

## Decision

Laravel 13 on PHP 8.3 or later; MySQL 8.0+ or MariaDB 10.6+ in production. Public pages render through Blade for reliable HTML, metadata and minimal JavaScript. A React 19/TypeScript administration application uses same-origin Laravel JSON endpoints and cookie sessions. Vite and Tailwind compile assets before deployment. No production Node process, SSR worker, Redis, Docker or paid service is required.

This deliberately uses server-rendered public pages rather than Inertia SSR, which would require Node for server rendering. React is concentrated in the interactive CMS. Laravel provides validation, prepared database queries, CSRF protection, password hashing, authorization, throttling, storage and migrations.

## Environment

PHP extensions: ctype, dom/XML, fileinfo, filter, GD with WebP, mbstring, openssl, PDO/pdo_mysql, session, tokenizer. Curl is recommended for HTTP tooling; core page rendering has no external API dependency. Composer 2 builds dependencies; Node 22.12+ builds frontend assets on the developer's computer. SQLite/pdo_sqlite is only required for the local development/test default. Apache/LiteSpeed routes requests through public/index.php. Only public/ is web accessible. Writable storage/ and bootstrap/cache/; production HTTPS and APP_DEBUG=false. Use PHP memory of 256 MB or more for upload resizing.

## Domain

Dedicated relational tables for users, settings, homepage sections, categories, services, professionals, branches, appointments, status history, messages, media, articles, tags, gallery, reviews, FAQs, custom pages, navigation and activity logs. Foreign keys and pivots express service/branch, professional/branch, article/tag and content relationships. Public publication queries enforce status and consent. Administrative requests are separately authorized against explicit roles.

## Commercial reuse

config/clinic.php supplies installation defaults and supported modules/presets. Database settings override them; content and reviewed translations live in database records. Central CSS tokens receive validated brand colors. Every clinic has its own database, media and environment. No tenant routing, billing or shared patient database.

## Privacy and operations

This stores inquiry contact details only, not medical records. Appointments are requests until clinic confirmation. Email uses synchronous configurable Laravel transport with graceful delivery failure; no required queue workers. Delivery status is recorded without logging message bodies. Consent is mandatory for patient requests and publication of identifiable clinical gallery items. CLI setup avoids a public installer. Backups, upgrades, retention and restore procedures are documented.

## Application boundaries

| Layer | Implementation |
| --- | --- |
| Public rendering | Blade layouts, reusable cards/sections, local typography/photos, minimal TypeScript interactions |
| CMS | React workspace, schema-driven field forms/repeaters, lazy Tiptap rich editor, same-origin JSON API |
| Validation and permissions | Laravel FormRequests/registry rules, explicit Access role matrix, active-account middleware |
| Settings | ClinicSettings request-scoped database/config merge; SettingsRegistry validates editable fields; CSS variables receive safe colors |
| Content | Dedicated models/tables, published/demo/consent scopes, reviewed translation JSON, relational pivots |
| Patient workflows | Transactional appointment/reference/history creation, contact storage, versioned status transitions, optional mail |
| Files | GD re-encoding to WebP, private local filesystem, public processed-image route, restricted uploads |
| Operations | Migrations, idempotent foundation seeder, private CLI account setup/recovery, readiness and retention commands |

`ContentRegistry` centralizes the supported CMS field schema, relation choices, validation, and module abilities. Controllers still enforce authorization on every request. The frontend consumes this schema but is never the permission boundary. `SafeContent` drops unsafe HTML elements/attributes/URLs before rich content is stored; public templates render only sanitized content as HTML and otherwise escape output.

## Relational design

Users carry one of four fixed roles rather than requiring dynamically editable permission tables. Every clinical installation is independent. Settings are JSON-valued key records; homepage sections have unique supported keys/order/visibility. Content entities have unique slugs, publication/demo flags, ordering, SEO, and reviewed translation objects. Repeated local content such as hours, treatment steps, and qualifications uses validated JSON, while independent entity relationships use foreign keys and pivots.

Category types distinguish services/articles/gallery. Branch/service, branch/professional, service/related-service, and article/tag relationships use junction tables. Appointments reference branch/service/professional and retain patient preferences, consent snapshot, notification state, timestamps, and an integer version. History references the appointment and updating user. Deleting related content nulls applicable request relationships; deleting an appointment through approved retention removes its status history. Contact messages and activity logs remain separate from publishable content. Media records retain storage paths, metadata, and stable public IDs.

All schema changes are migrations. `ClinicFoundationSeeder` creates supported sections/navigation idempotently without customer records/accounts. `DemoSeeder` adds explicitly flagged samples only when requested. Public queries hide drafts, future articles, unapproved/consentless clinical material, and demo records when demo mode is disabled. Booking validation applies the same demo/publication restrictions and branch relationships.

## Localization and SEO

UI strings live in `lang/en` and `lang/am`. Content/settings store primary English and optional reviewed Amharic translations; blank translations fall back to English. Hero/about text and its section editor stay synchronized. The locale middleware supports a session preference and explicit query parameter. Canonical/sitemap/hreflang URLs use explicit language parameters when multilingual, so English links remain usable after an Amharic session.

Meta descriptions, page titles, social metadata, breadcrumbs, XML sitemap, dynamic robots, and valid JSON-LD render on the server. Entity schema describes actual supplied services/professionals/branches; demo profiles do not assert Person credentials or ratings. No aggregate-rating schema is fabricated. Pricing is optional ETB. Dates use Gregorian operational dates and Africa/Addis_Ababa consistently.

## Hosting and build

Public pages use no React hydration or Node SSR. Admin/editor assets load separately; the rich editor is deferred until needed. The clean release builder installs production Composer dependencies in isolated staging and copies an allowlist of code, assets, views, migrations, notices, and empty writable directories. It excludes environment, data, credentials, tests, Node modules, and generated development caches.

Preferred hosting points the domain at the private application's public directory. A fixed `public_html` account instead receives only public contents and an adjusted front controller. `APP_PUBLIC_PATH` configures the same public root for CLI/image/readiness restrictions. Processed media is served through Laravel; originals never need a public symlink. Private cron commands provide a setup alternative where SSH/Composer are unavailable.

The package smoke tool exercises a clean production-dependency archive using a new SQLite database and a separate public directory, including key generation, migrations/seeding, private account-file deletion, cached configuration/routes/views, public assets, and private-file isolation. Local simulation does not replace the customer host's Apache/LiteSpeed and SMTP acceptance checks.

## Deliberate tradeoffs

This is a clinic marketing/CMS/inquiry system. It has no real-time availability promises, EMR, payment gateway, multi-tenancy, billing, persistent queue, external image service, or generic visual page builder. Maps use HTTPS links rather than embedded tracking frames. Email is synchronous with a configurable timeout and stored delivery outcome. Failure leaves patient requests intact for telephone follow-up.

Private originals and patient data require operational protection/backups; database patient fields are not automatically encrypted by application casts. Retention cleanup previews closed requests and requires explicit execution. Additional languages, MFA, SMS, calendar conversion, provider-specific integrations, and new sections can be added through documented extension points with migrations/validation/tests.
