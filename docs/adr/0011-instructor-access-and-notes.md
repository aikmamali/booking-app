# ADR 0011: Limited admin access for instructors, and trainer notes

- Status: accepted
- Date: 2026-11-28
- Decided by: Katerina (instructors get limited admin access)
- Amends: ADR 0002

## Context

RP-03 lets instructors keep notes about their customers (an exercise log), and some notes are visible to the customer. Until now ADR 0002 kept instructors out of the admin panel.

## Decision

- **Instructors enter `/admin`, with two-step verification like every staff member.** They get only the permissions of the notes migration:
  - `page_MyClasses`: their own classes and participants.
  - `*_note`: notes.

  Everything else (users, payments, expenses, memberships, schedules, settings) stays hidden, because those policies check permissions instructors do not have. The dashboard figures need `view_any_payment` (key figures) or `view_any_class::session` (today's classes).
- **Reach.** An instructor reaches a customer only if the customer has a held booking (confirmed, attended or no-show) in one of their classes. The Booking module answers this through the `InstructorRoster` contract. Communication never reads the bookings table.
- **Notes** (`notes` table, Communication module):
  - Each note has the customer, an optional booking, the author, the text and `visible_to_customer`.
  - The spec's `notable` morph is split into `user_id` and an optional `booking_id`, because a booking always implies its customer.
  - Notes are soft-deleted and audited.
  - Instructors see all notes about the customers they reach, so a colleague's notes are useful context, but they change only their own.
  - Admins (`manage_all_notes`) see and change everything.
- **Customer side.** Visible notes appear on "My membership", with the date, the author and the class.

## Consequences

- When reception arrives, it gets its own permissions. Whether it should see notes (`view_any_note`) is a separate choice.
- An instructor's reach ends when all their bookings with that customer are cancelled. Notes stay, but drop out of that instructor's view.
