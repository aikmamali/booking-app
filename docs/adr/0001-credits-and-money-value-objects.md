# ADR 0001: Credits and Money as exact value objects

- Status: accepted
- Date: 2026-11-21

## Context

Credits move in steps of 0.5 (spec section 4, `credit_cost`) and are stored as `decimal(6,2)` (spec section 6). Money is stored in integer cents. Floats are forbidden for both, because rounding errors would break the ledger invariant (balance = SUM of transactions, never below zero).

## Decision

- `App\Shared\Credits` holds an integer count of **halves**. It is parsed from and formatted to decimal strings (`"1.50"`), never from floats. Values that are not multiples of 0.5 are rejected at construction. Negative values are allowed, because ledger entries are signed.
- `App\Shared\Money` holds integer **cents** plus a currency code (EUR by default). Operations across currencies throw.
- Both are `final readonly` and immutable. Settings store `Credits` through `App\Shared\Settings\CreditsCast`.

## Consequences

- Arithmetic is exact, and comparisons are integer comparisons.
- Eloquent attributes need a cast to and from these objects (added with the Membership module).
- Locale-aware display ("1,5 credits", "12,50 €") belongs to the UI layer, not to the value objects.
