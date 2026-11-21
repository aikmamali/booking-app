# ADR 0007: Customer notifications, preferences and templates

- Status: accepted
- Date: 2026-11-21

## Context

CM-01, CM-02, CM-04 and spec 8.1: transactional emails for booking, waitlist and membership events, customer channel choices, staff-editable wording and home page announcements. No SMS provider has been chosen yet (spec 12).

## Decision

- **One notification class.** `CustomerNotification` (queued, no readonly properties) carries a `NotificationType`, the placeholder values, an action link and an optional .ics attachment. `SendCustomerNotifications` maps each domain event to a type. It reads other modules only through `BookingSummaries` and `MembershipSummaries`. Anonymised users are never notified.
- **Channels per customer.**
  - `notification_preferences` stores type × channel choices. A missing row means the type's default: email for everything, plus SMS for session cancellations and waitlist messages, as in spec 8.1.
  - Marketing consent is separate and recorded with its time.
- **SMS behind an interface.** `SmsSender` is bound to `LogSmsSender` until a provider is chosen; a real adapter replaces one binding. An SMS carries the subject line only.
- **Editable templates.** `email_templates` has one row per type (created by the migration), with translatable subject and body. An empty translation falls back to `lang/{el,en}/communication.php`. Placeholders use the `:name` form, and each type lists the ones it supports for staff. A blank line in the body starts a new paragraph.
- **Announcements.** Translatable title and body, an optional image, a publication window and a manual order. The customer home shows them as a horizontal carousel, through the `Announcements` contract.

## Consequences

- Fixed-seat and receipt notifications are added when those features exist.
- Switching on SMS needs only an adapter class and a provider key in `.env`.
