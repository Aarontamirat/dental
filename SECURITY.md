# Security and data handling

## Implemented controls

- Laravel cookie sessions, password hashing, active-account enforcement, individual roles, server authorization, and private administration routes.
- Session regeneration on sign-in, logout invalidation, revocation on account recovery/deactivation, encrypted sessions, and production secure/HTTP-only/SameSite cookies.
- CSRF middleware for mutations, prepared ORM queries, server validation, escaped Blade output, restricted slugs/URLs, and server-side rich HTML sanitization.
- Rate limits: five patient submissions per minute per IP, five login attempts per minute per email/IP, and five reset requests per hour per IP.
- Patient consent snapshots, honeypot, encrypted form-age token (2 seconds to 4 hours), visit/date/hours/branch checks, and request references.
- Optimistic appointment versions reject stale staff edits; status transitions/history and audit records support review.
- JPEG/PNG/WebP validation, file/dimension limits, image re-encoding, private originals, UUID paths, and referenced-image deletion restrictions.
- CSP, no framing, nosniff, restricted browser permissions, referrer policy, HTTPS HSTS, and no-store/noindex for administration/recovery.
- CSV formula escaping, SMTP fallback without losing requests, and minimal notification bodies without internal patient messages/notes.

These controls have automated verification in [TESTING.md](TESTING.md). They do not replace correct hosting permissions, staff training, clinic review, or backups.

## Data boundaries

The application collects names, phone/email, service/branch/professional preferences, visit date/time, a short inquiry, and consent. It is not a diagnosis, prescription, clinical history, payments, or EMR platform. Patient forms discourage unnecessary medical details. Internal notes should describe request administration only.

Staff permissions restrict patient inbox/export access to appointment managers and administrators. Content editors cannot read patient inquiries. Sessions are encrypted; patient database fields are not automatically application-encrypted at rest. Use the host's approved database/disk protections and encrypted backups, with database credentials limited to the dedicated installation.

Optimized media URLs are public even when the image is not featured on a page. Originals remain private. The media library is for approved website assets, never confidential records or unapproved clinical photographs. Gallery publication enforces consent/reference checks; signed consent belongs in the clinic's separate secure records. Changing consent cannot retract copies someone already downloaded.

## Deployment requirements

Set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, secure cookies, and a unique existing installation key. Keep the entire application outside the document root except public assets/front controller. Never expose `.env`, Composer files/vendor source, private storage, backups, or setup JSON/logs. Use the explicit `APP_PUBLIC_PATH` setting for a separate public_html layout so HTTP and CLI agree on the public root.

Do not commit credentials, copy keys across customers, publish the demo access file, or ship caches containing a developer's environment. The release builder uses an allowlist and fresh production dependencies. Protect hosting, database, mail, and staff accounts with individual access and appropriate passwords.

No public installer, account registration, SMS gateway, or arbitrary external embedding is present. Maps open approved HTTPS links. Core pages render using local assets. CSP allows local/data/blob images for upload previews but blocks external frames/scripts. Adding analytics, embeds, external storage, or additional HTML elements requires a deliberate privacy/CSP review and updated notices.

## Operations

Keep Laravel/PHP/JavaScript dependencies patched using lockfiles, a reviewed update branch, staging verification, and controlled deployment. Run `composer audit`, `npm audit`, backend/browser checks, and [backup drills](BACKUP_AND_RESTORATION.md). Monitor mail failure indicators, disk quota, access issues, and server errors. Do not enable debug publicly to troubleshoot; inspect restricted logs.

Set a clinic-approved inquiry retention period and preview cleanup before allowing deletion. Separate policies are needed for backups/media/logs and signed consent. Privacy/terms/cookie content is editable template material; the clinic must identify its responsible contact, purposes, recipients, retention, and request-handling process before publishing.

MFA is not implemented in this foundation. For stronger administrator access, use an approved hosting access layer or add maintained MFA functionality with recovery and tests. This build is not a certification of regulatory compliance, a penetration test, or a guarantee that a particular hosting account is secure.

## Incident response

Restrict the affected access, preserve appropriate logs/backups privately, and notify the clinic's responsible operator. Revoke affected staff sessions/accounts and rotate compromised hosting/database/SMTP credentials. Preserve `APP_KEY` unless compromise requires a planned key rotation; replacing it invalidates encrypted sessions. Review affected records, restore from a verified compatible backup if necessary, patch the cause, and validate before reopening.

Report suspected vulnerabilities privately to the product/clinic operator using their established contact. Do not publish patient data, exploit details, keys, or passwords in public issues.
