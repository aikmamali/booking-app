# ADR 0012: Bulk messages

- Status: accepted
- Date: 2026-11-28

## Context

CM-03: staff send an email and/or SMS to a filtered audience. The message goes through the queue, and customers without consent are left out.

## Decision

- **Consent first.** Recipients are customers with `marketing_consent_at`, not anonymised. Consent is checked again at send time, so a withdrawal after queueing is respected. Emails end with a line that points to the profile, where consent is changed.
- **Filters.** The filters are an active membership, a membership that ends within N days, and a membership that covers a service. One membership must match all the chosen filters. The Membership module answers through the `MembershipAudience` contract. Without filters, the message goes to every consenting customer.
- **Recipients fixed at queue time.** `broadcast_recipients` stores the list when the message is queued. `SendBroadcastJob` sends batches of 100, marks each recipient as sent and queues itself for the next batch. A retry therefore never reaches someone twice or someone new.
- **SMS.** SMS goes only to customers with a phone number. The text is the subject plus the start of the body, cut to one message (155 characters including "..."). The same limit now applies to transactional SMS, which could reach 158 characters before.
- **Permissions.** `view_any_broadcast` and `create_broadcast` belong to the admin only. Instructors do not get them.
