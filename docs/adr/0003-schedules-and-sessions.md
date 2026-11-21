# ADR 0003: How weekly schedules and generated sessions relate

- Status: accepted
- Date: 2026-11-21

## Context

SC-02 to SC-04 need weekly schedules that fill a calendar of real `class_sessions` rows (spec 6.2), single-session edits that survive later schedule edits, and closed days. The spec does not say what happens to already generated sessions when a schedule or a closure changes.

## Decision

- **Wall-clock times.** Schedules store weekday and start time in the business time zone (`BookingPolicySettings::timezone`, Europe/Athens). Sessions store UTC instants, computed per date, so 18:30 stays 18:30 across daylight saving changes.
- **Idempotent generation.** `GenerateSessions` inserts with `insertOrIgnore` on `UNIQUE(schedule_id, starts_at)`. Running it twice changes nothing, and it never revives a cancelled or edited session. It runs nightly at 02:00 Athens time, after a schedule is saved and after a closure is removed.
- **Fixed slot.** A schedule's day and time cannot change after creation. To move a class, staff end the schedule (`valid_until`) and create a new one. Silently moving booked sessions would surprise customers.
- **Propagation.** Instructor, room and capacity changes to a schedule apply to its future sessions that staff have not edited one by one (`is_customized`). Capacity never drops below the seats already taken.
- **Cancel, never delete.** Shortening a schedule's validity or adding a closure cancels the affected future sessions with a reason and dispatches `SessionCancelled`. Refunds (step 4) and notifications (step 6) react to that event. Removing a closure generates the missing sessions again but leaves the cancelled ones cancelled, because customers were already told.
- **Optimistic locking.** Single-session edits carry `lock_version`; a stale edit is rejected instead of overwriting another staff member's change.
- **Occupancy contract.** Scheduling asks `SessionOccupancy` how many seats are taken. It is a no-op until the Booking module binds the real implementation, so Scheduling never reads Booking tables.

## Consequences

- SC-04 "opening hours" is covered by closures only: the weekly schedule already defines when classes run.
- Moving a recurring class to another day or time takes two steps in the admin, which is deliberate.
