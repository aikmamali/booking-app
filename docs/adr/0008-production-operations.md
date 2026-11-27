# ADR 0008: Production operations

- Status: accepted
- Date: 2026-11-27

## Context

Step 10 prepares the first production run (spec 11 and 12): an installable PWA, zero-downtime deploys, backups, monitoring and security headers, on one small VPS.

## Decision

- **PWA.** A web manifest with generated icons, including a maskable one. The service worker fetches pages from the network so bookings and balances are never stale, shows an offline page without a connection, and caches only hashed build assets and icons. The customer area works from 360 px with no horizontal scroll.
- **Deploys.** Deployer (`deploy.php`, recipe/laravel) creates symlinked releases on the server: it installs vendors, builds the assets on the server, then runs `migrate --force`, optimise, `filament:optimize` and `queue:restart`. `.github/workflows/deploy.yml` deploys `main` only after CI is green, and skips itself until the server secrets exist.
- **Backups.** `spatie/laravel-backup` dumps the database and the uploaded images (the code lives in git) to `BACKUP_DISK`, which is an S3-compatible bucket in production. Clean-up runs at 03:00 and the backup at 03:10, Athens time. Retention is 30 daily and 12 monthly copies. A monitor at 09:00 emails when there is no fresh backup, and only failures send mail. A restore test once a quarter is a manual step in `docs/DEPLOYMENT.md`.
- **Monitoring.**
  - Uptime monitors check `/up`.
  - Sentry reports exceptions once `SENTRY_LARAVEL_DSN` is set.
  - Failed queue jobs are logged as critical and mailed to `ALERT_EMAIL`.
- **Security headers.** All responses carry `X-Frame-Options: DENY`, `nosniff`, a referrer policy, a permissions policy and a CSP limited to this origin plus Filament's font CDN. The CSP allows `unsafe-inline` and `unsafe-eval` because Livewire, Alpine and Filament need them. HSTS is sent only in production over HTTPS.
- **No third-party avatars.** Filament's default avatar URL sent staff names to ui-avatars.com. A local initials avatar replaces it (GDPR; the CSP blocks the external one anyway).

## Consequences

- The server needs Node 22 for the asset build, and the MySQL client for the backup dumps.
- The first administrator is created with `identity:create-admin` after the first deploy.
