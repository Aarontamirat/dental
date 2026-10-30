# Backup, restoration, and upgrades

## What to protect

Back up the clinic's database, `storage/app/private` (optimized media and originals), private `.env` including the existing `APP_KEY`, and the deployed release/front-controller customization. The database contains branding, translations, content, accounts, consent snapshots, patient requests, status history, and audit records. A database-only backup cannot restore uploaded images.

Compiled assets and bundled fonts/photos can be recovered from the same release, but retain the exact release ZIP/checksum. Logs/session caches are operational artifacts, not the core restore set. Backups include patient information and credentials: encrypt them, restrict access, store them outside the public root, and keep a separate copy outside the hosting account.

Agree retention and recovery objectives with the clinic. A reasonable starting operation is nightly database/media backups, a weekly full backup, and a backup before every upgrade, with retention approved by the clinic. Review cPanel backup limits and ensure recovery is possible without the failed account.

## cPanel backup

1. Use cPanel Backup/Backup Wizard to export the dedicated MySQL database. Confirm the full cPanel database name.
2. Archive/download private `storage/app/private`, `.env`, and release identification through authenticated File Manager/SFTP, outside all public roots.
3. Encrypt and move copies to the agreed secure backup location. Never leave a ZIP/SQL dump in `public_html` or `public`.
4. Record the timestamp, release/checksum, file size, database name, and result. Periodically verify that archives can be read and restored.

For a CLI database export use `mysqldump` with a secure option file or interactive password prompt, `--single-transaction`, `--routines`, and `--triggers` as supported by the provider. Never put a password directly on the command line. Do not export other customers' databases.

## Restore drill

Perform drills in an isolated staging account/database, with SMTP disabled and public access restricted. Restore testing must not overwrite the working clinic database.

1. Prepare compatible PHP/MySQL and deploy the exact release. Set only the intended public document root.
2. Create a new isolated database/user, then import the matching SQL export using cPanel/phpMyAdmin or approved CLI tooling.
3. Restore private media paths exactly. Restore the original `.env`/`APP_KEY`, then replace database credentials/domain for the staging account and disable email.
4. Clear configuration/routes/views and generate caches for the restored account. Check `clinic:check`; intentional staging differences from production may need separate verification.
5. Verify known content/images, translations, account access, a historical request and its status history, and a new test request. Confirm private files cannot be downloaded.
6. Record the result and recovery duration. Remove or retain staging data according to the clinic's agreed process.

Production restoration overwrites data and requires the clinic/operator's explicit authorization, a clear target, maintenance time, and a fresh backup of the current state where possible. Restoring an older database loses later changes; reconcile outstanding requests with staff. Do not generate a new key to repair an existing installation: encrypted sessions depend on the original key.

## Upgrade

Build/test a new locked release first. Back up database, private media, environment, and current code. Test migrations and major journeys on a restored staging copy. Arrange maintenance, then use `php artisan down` while deploying if appropriate.

Deploy code/assets together while preserving the existing `.env`, key, database, and `storage/app/private`. For fixed `public_html`, preserve the adjusted front controller. Run `php artisan migrate --force`, `php artisan package:discover`, and regenerate config/routes/views on the host. Run `clinic:check`, smoke-test patient/admin journeys, then `php artisan up`. Check the inbox after reopening.

Do not run `migrate:fresh`, development seeders, or dependency updates against an existing clinic. The foundation seeder is idempotent, but demo seeding should be confined to demonstrations. Rollback should restore a compatible code/database/media set; arbitrary migration rollback can discard data.

## Retention and individual requests

`php artisan clinic:prune-inquiries` previews expired closed appointments and resolved messages using the configured retention period. It deletes nothing by default. After the responsible operator reviews counts and approved backups, `--execute` permanently deletes matching records and associated appointment status history. Active/pending requests remain.

Only schedule deletion after an approved retention policy and successful drill. This cleanup does not delete old backups, logs, or media. Coordinate their retention separately. An individual access/correction/deletion request needs authorized operator handling; the product does not expose a public identity-verification or patient-record portal.
