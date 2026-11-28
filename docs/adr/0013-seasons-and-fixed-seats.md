# ADR 0013: Seasons and fixed seats

- Status: accepted
- Date: 2026-11-28

## Context

FS-01 to FS-04 (spec 7.4): customers hold a standing place in a weekly class for a season. Their bookings are made before the class opens to everyone else. Without credits the place is held for a while, and too many cancellations in a row cost the seat.

## Decision

- **Seasons** never overlap, so every date belongs to at most one season. A finished season ends its seats and open requests (`EndFinishedSeasonsJob`, 00:15 Athens).
- **Requests and approval.**
  - A customer requests a seat in a weekly class that runs during the season.
  - Requested and active seats both hold a place, so a class never has more seats than its capacity. Concurrent requests and approvals are serialised on the season row (`FixedSeatPlaces`), which keeps Booking off the Scheduling tables.
  - Staff approve the request, or reject it with a reason. Staff can also assign a seat directly.
  - Rejected and revoked seats share the `revoked` status, as in spec 6.1. The reason tells them apart.
- **Booking.**
  - `BookFixedSeat` books every upcoming class of the schedule inside the season. It runs on approval and after every `GenerateSessions` run (listener on `SessionsGenerated`), so the classes are taken before `booking_opens_days_ahead`.
  - Fixed-seat bookings go through `BookSession` with the rule chain's override switch. This skips the booking window and the daily and active limits, while capacity, credits and duplicates still apply.
  - A class the seat already has a booking for (in any status) is never booked again. A cancelled class therefore stays cancelled.
- **No credits.** The place is held as `pending_credits` until `hold_until` = now + `fixed_seat_pending_credits_hours`, and never past the class start. The customer is notified.
  - Buying a package (`MembershipSold`) confirms and charges the held places, soonest first, while the credits last.
  - `ExpirePendingBookingsJob` (every minute) expires holds that run out and frees the seat for the waitlist.
- **Cancellations in a row.**
  - On every cancellation of a fixed-seat booking, the streak is recounted from history: from that class back to the last class that was not cancelled. Reschedules, class cancellations by the studio and cancellations caused by a revocation do not count.
  - At limit − 1 the customer is warned. At the limit the seat is revoked: future bookings are cancelled with a refund, their seats are freed, and the customer is notified.
  - Attending resets the stored counter. The count of record is the history, so a stale counter cannot revoke a seat by mistake.
- **Customer side.** "My fixed seats" (`/app/fixed-seats`, linked from "My bookings") shows the seats. The customer can request a seat or give one up. Bookings already made stay, and the customer cancels them one by one.
- **Notifications.** Five new editable types: approved, rejected, waiting for credits, warning and revoked. The last two also go by SMS by default.
