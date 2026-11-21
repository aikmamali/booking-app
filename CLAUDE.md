# CLAUDE.md

Booking & memberships app for a single business (fitness studio). Customers book classes with credit packages; staff manage schedule, memberships, payments and communication.

The full specification is in `docs/SPEC.md` (Greek). The MVP plan is in `docs/MVP_PLAN.md`. Read both before starting a task. When something here conflicts with the spec, the spec wins. Ask before changing a decision in the spec's section 14.

## Communication
- Talk to me (Katerina) in Greek. Code, identifiers, commits and code comments are in English.
- UI strings live in `lang/el` (default) and `lang/en`. No hard-coded user-facing text.

## Stack
- PHP 8.3+, Laravel 12, MySQL 8
- Filament 3 for `/admin` (staff), Livewire 3 for `/app` (customers, PWA)
- Packages: spatie/laravel-data, spatie/laravel-settings, spatie/laravel-permission (+ Filament Shield), spatie/laravel-activitylog, spatie/laravel-translatable, spatie/laravel-backup
- Queue: database driver. Scheduler: `schedule:run` every minute.
- Tooling: Pint, Larastan (level 8), Rector, Pest + arch plugin

## Architecture (spec section 5)
Modular monolith under `app/Modules/<Module>/` with: `Actions/ Data/ Enums/ Events/ Exceptions/ Jobs/ Listeners/ Models/ Policies/ Rules/ Filament/ Livewire/ <Module>ServiceProvider.php`.
Modules: Identity, Catalog, Scheduling, Booking, Membership, Billing, Communication, Reporting. Shared value objects in `app/Shared/`.

Rules:
- One use case = one `final` Action class with a single `handle()` method. Filament and Livewire call Actions; they contain no business logic and no business queries.
- `DB::transaction` only inside Actions. Domain events implement `ShouldDispatchAfterCommit`.
- A module never queries another module's tables. Use its public contract (e.g. `CreditLedger` interface) or domain events.
- External services (payments, receipts, SMS) sit behind interfaces with adapters bound in the ServiceProvider; tests use fakes.
- Business numbers (cut-offs, limits, capacities) come from `BookingPolicySettings`, never literals.
- Booking checks are `BookingRule` classes run by `BookingRuleChain` (spec section 4).
- State changes go through backed enums with `canTransitionTo()`.
- Credits: `credit_transactions` is append-only. Never UPDATE a balance. Money in integer cents, credits as `Credits` value object (decimal 0.5 steps). No floats for money.
- Store times in UTC. Display in `Europe/Athens`. Rules receive the time via `BookingContext`; no bare `now()` inside rules.
- Public URLs use `public_id` (ULID), never auto-increment ids.
- `declare(strict_types=1);` everywhere. Typed params/returns. Small methods (~20 lines).
- `Model::preventLazyLoading()` in local and testing.

## Testing
- Pest. Feature tests run on MySQL, not SQLite (row locks matter).
- Every `BookingRule` gets unit tests for each branch.
- Every Action gets feature tests for the happy path and each rejection.
- Time-sensitive tests use `travelTo()`. Include boundary cases (179/180/181 minutes).
- Keep the concurrency test for the last seat and the ledger invariant test green.
- Arch tests in `tests/Arch` enforce the layering rules above.

## Commands
- `./vendor/bin/sail up -d` – start local stack
- `./vendor/bin/sail artisan migrate:fresh --seed` – reset DB with demo data
- `./vendor/bin/sail pest` – tests
- `./vendor/bin/sail pint` – format
- `./vendor/bin/sail php vendor/bin/phpstan analyse` – static analysis

## Workflow
- Work through `docs/MVP_PLAN.md` in order, one step at a time. Each step ends with green Pint, PHPStan and Pest, then a commit (Conventional Commits, e.g. `feat(booking): add BookSession action`).
- Tick the step in `docs/MVP_PLAN.md` when done.
- Migrations only move forward. Never edit a migration that has been committed.
- Record significant design decisions as ADRs in `docs/adr/`.
