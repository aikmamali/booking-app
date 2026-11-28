# ADR 0010: Freezing and extending memberships

- Status: accepted
- Date: 2026-11-28

## Context

MB-06: staff pause a membership (e.g. for an injury) or extend it. The end date moves and the change is audited.

## Decision

- **A freeze starts today and runs until a chosen date** (`frozen_until`, the day it resumes). The end date moves later at once by the paused days. Customers and staff then see the real end date during the pause, and the expiry and warning jobs need no special case.
- **A frozen membership does not pay.** FEFO picks active memberships only. Bookings made before the freeze stay as they are: the Membership module cannot see bookings (module boundary), and staff can cancel them by hand.
- **Resuming.** It happens by itself at 00:05 on `frozen_until` (`ResumeFrozenMembershipsJob`). Staff can also resume early, and then the unused paused days come off the end date again.
- **Extending** adds days to an active or frozen membership with a reason. An expired membership cannot be extended, because its credits were already written off. Staff sell a new package or adjust credits instead.
- **Audit.** Freeze, resume and extension are written to `activity_log` with the old and new end dates and the reason. They are not ledger movements, because credits do not change.
- **Permission.** Freeze, resume and extend use the existing `update_membership` permission.
