# Booking app

Booking and memberships app for a single fitness studio. Customers book classes with credit packages at `/app` (Livewire, PWA); staff manage schedule, memberships, payments and communication at `/admin` (Filament).

- Specification (Greek): [`docs/SPEC.md`](docs/SPEC.md)
- MVP plan: [`docs/MVP_PLAN.md`](docs/MVP_PLAN.md)
- Conventions for contributors and Claude Code: [`CLAUDE.md`](CLAUDE.md)
- Architecture decisions: [`docs/adr/`](docs/adr)

## Stack

PHP 8.3, Laravel 12, MySQL 8.4, Filament 3, Livewire 3, Tailwind 4. Modular monolith under `app/Modules/<Module>`, shared kernel in `app/Shared`.

## Local setup (Sail)

```bash
cp .env.example .env
docker run --rm -v "$(pwd)":/opt -w /opt laravelsail/php83-composer:latest composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

App: http://localhost/app · Admin: http://localhost/admin · Mailpit: http://localhost:8025

## Production

See [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) (Greek): server setup, queue worker, scheduler, zero-downtime deploys with Deployer, backups, monitoring.

## Quality gates

Each step of the MVP plan ends with all three green (CI runs them in this order):

```bash
./vendor/bin/sail pint --test
./vendor/bin/sail php vendor/bin/phpstan analyse
./vendor/bin/sail pest
```

`composer refactor:check` runs Rector in dry-run mode.
