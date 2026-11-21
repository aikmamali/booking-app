# ADR 0006: Waitlist promotion and held seats

- Status: accepted
- Date: 2026-11-21

## Context

WL-01 to WL-03 and spec 7.3 describe a queue per full session with two promotion strategies, `auto_book` and `offer`. Section 14 left the default open; the spec's settings table says `auto_book`.

## Decision

- **Both strategies, `auto_book` by default.** They switch with `waitlist_strategy` in the settings. No promotion happens inside `waitlist_cutoff_minutes` (default 60).
- **Queue order is the entry id order.** A customer who leaves and joins again goes to the back. A stored `active_key` column with a unique index allows one live entry per customer and session. Joining reserves no credits.
- **Promotion is triggered by `SeatFreed`.** Cancellations, reschedules and declined offers dispatch it after commit, and it queues `PromoteFromWaitlist`. That action locks the session row, fills free seats from the front of the queue, and:
  - with `auto_book`, books through `BookSession::bookLocked` with source `waitlist`. An entry that cannot be booked (no credits, daily limit) expires, and the next one is tried;
  - with `offer`, holds the seat until `offered_until`: the TTL (30 minutes), but never past the cut-off.
- **Held seats count as taken.** `SeatCounter` adds unexpired offers held by other customers to the active bookings. A direct booking therefore cannot take a seat reserved for someone else, and Scheduling cannot shrink a session below its held seats.
- **Expiry.** `ExpireWaitlistOffers` runs every minute. It expires lapsed offers and moves the seat to the next customer, and it expires live entries of sessions inside the cut-off. A cancelled session also expires its queue.
- **Emails.** Communication sends "you got a seat" (with .ics) on `WaitlistPromoted`, and "accept by …" on `WaitlistOffered`.

## Consequences

- Customers see their place in the queue in the booking flow and in "My bookings", where they can also accept or decline an offer.
- Raising a session's capacity does not promote anyone by itself; the next freed seat or the minute job does.
