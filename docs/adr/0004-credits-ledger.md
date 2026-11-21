# ADR 0004: Credits ledger and membership selection

- Status: accepted
- Date: 2026-11-21

## Context

Spec 6.2 and 7.5 require an append-only `credit_transactions` ledger from which every balance is derived, FEFO selection when a customer holds several memberships, and a public `CreditLedger` contract that the Booking module uses without touching Membership tables. Section 14 left "unlimited" packages open; Katerina decided against them for phase 1.

## Decision

- **Credit packs only.** `packages.credits` is required. Unlimited packs with a weekly limit can be added later as a new column and a booking rule.
- **Snapshot on sale.** A membership copies the package name, credits, price, VAT rate, validity and covered service ids at the moment of sale. Later package edits never change sold memberships.
- **Append-only ledger.** `DatabaseCreditLedger::record()` locks the membership row (`SELECT … FOR UPDATE`), reads the latest `balance_after`, and inserts one row. It refuses:
  - a wrong sign for the movement type (debit and expire are negative, purchase and refund positive, adjustment either way);
  - a zero amount;
  - an adjustment without a reason;
  - any movement that would take the balance below zero.

  The model throws on update and delete. Writes must happen inside a transaction opened by an Action.
- **Invariant.** The latest `balance_after` equals `SUM(amount)` and no `balance_after` is negative. A property-style test runs 60 random movements for 10 seeds.
- **Public contracts.** Booking depends only on `CreditLedger` (`balance`, `debit`, `refund`, keyed by ids) and `MembershipSelector`. The selector returns a `MembershipChoice` that says whether nothing qualifies (`booking.no_membership`) or something qualifies but lacks credits (`booking.insufficient_credits`).
- **FEFO.** Of the active memberships valid on the session date that cover the service and have enough credits, the one with the earliest `ends_on` pays.
- **Payment hand-off.** Selling a package dispatches `MembershipSold` with the price and payment method. Billing records the payment from that event in step 7, so Membership never writes Billing tables.
- **Expiry and warnings.** A nightly job expires memberships after their last local day and writes off the remaining credits with an `expire` entry. A daily job flags memberships that are expiring soon or low on credits, once per membership each. The notifications themselves arrive with Communication in step 6.

## Consequences

- Reading a balance is one indexed query on `(membership_id, id)`; no SUM is needed.
- Until step 7, a sale is recorded in the ledger but not yet as a payment.
