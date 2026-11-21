# ADR 0005: Booking flow, rules and attendance

- Status: accepted
- Date: 2026-11-21

## Context

Step 4 implements BK-01, BK-02 and BK-04 to BK-09 (spec 3.3, 4, 7.1–7.3). Three things needed deciding: how to prove there is no overbooking, which rules staff may bypass, and what happens after a class when there is no check-in at the door (spec section 14).

## Decision

- **Row lock plus unique index.** `BookSession` locks the `class_sessions` row (`SELECT … FOR UPDATE`) before counting seats, so concurrent bookings for one class run one after the other. A stored generated column `active_key` with `UNIQUE(user_id, class_session_id, active_key)` is the second line of defence against double booking.
  - `tests/Concurrency/LastSeatTest` forks two processes with their own MySQL connections and pauses the first right after it counts seats.
  - It passes with the lock and fails with two bookings without it.
- **Pure rules.** `BookingContextFactory` gathers every fact once (seats taken, existing bookings, the FEFO membership choice, forms, the time from `Clock`). Each `BookingRule` is a pure function of that context and has a unit test for every branch.
  - The chain order: `SessionIsBookable`, `NotAlreadyBooked`, `HasCapacity`, `WithinDailyLimit`, `WithinActiveLimit`, `HasValidMembership`, `RequiredFormsCompleted`.
  - `WithinActiveLimit` was added for the `max_active_bookings` setting.
- **Staff override (BK-07).**
  - Overridable: the booking window, the daily and active limits, and the forms.
  - Never overridable: capacity, cancelled or started sessions, double booking and credits.
  - An override needs the `override_booking_rules` permission and a reason, and it is written to the activity log. Staff may also refund a late cancellation with a logged reason.
- **Cut-off is inclusive.** At 181 or 180 minutes before the start the credit is refunded; at 179 it is not. A reschedule uses the same cut-off, refunds and debits in the same transaction, and is not a cancellation for the fixed-seat streak.
- **Attendance.** Katerina decided there is no check-in at the door in phase 1. The new setting `checkin_required` defaults to false, so `MarkNoShows` (every 15 minutes) closes finished bookings as `attended`. Turning the setting on makes them `no_show` instead. Admins can still mark attendance by hand.
- **Cascades.**
  - Cancelling a session cancels and always refunds its bookings, through a listener on `SessionCancelled`.
  - Deleting an account cancels future bookings with a refund, through a listener on `UserDeleted`.
  - `SessionOccupancy` is now backed by real bookings, so a session can never shrink below its bookings.
- **Emails.** The confirmation (with an .ics attachment), cancellation and reminder emails live in the Communication module as queued notifications. They read bookings through the `BookingSummaries` contract, never the Booking tables. Reminders go out once per threshold in `reminder_hours_before`. A booking made after a threshold has passed skips that reminder.

## Consequences

- Waitlist sizes and promotion arrive in step 5. The `SeatFreed` event is already dispatched after every cancellation and reschedule.
- A queued notification must not use readonly properties: the queue cannot rebuild them. A regression test covers every queued notification.
