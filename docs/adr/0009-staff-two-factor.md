# ADR 0009: Two-factor authentication for staff

- Status: accepted
- Date: 2026-11-28

## Context

ID-07 and spec 11 make TOTP mandatory for staff roles. Filament 3 has no built-in multi-factor support.

## Decision

- **TOTP in the Identity module.** `pragmarx/google2fa` computes RFC 6238 codes (30-second steps, one step of drift either side). The QR code is drawn locally with `bacon/bacon-qr-code`, so the secret never goes to a third party.
- **Storage.** `users` gains `two_factor_secret` and `two_factor_recovery_codes` (both encrypted with the app key), `two_factor_confirmed_at` and `two_factor_last_step`. 2FA counts as enabled only after the first code is confirmed. The last accepted step is stored so the same code cannot be used twice.
- **Who.** Everyone who can open `/admin` (`User::requiresTwoFactor()`); today that is the admin role, later reception. Customers are not affected.
- **Flow.** The `RequireTwoFactor` middleware runs after Filament's `Authenticate` and is persistent, so Livewire requests from admin pages are checked too. Without 2FA it sends the user to the setup page. With 2FA it sends them to the challenge once per login session. The pass is stored in the session, which logout invalidates.
- **Recovery.** 8 one-time recovery codes, shown once and replaceable. If both the phone and the codes are lost, another admin resets 2FA (audited). Nobody can reset their own.
- **Limits.** 5 challenge attempts per minute per user, as for the login.
- **Tests.** The suite runs with `TWO_FACTOR_ENFORCED=false`, so the existing admin tests stay focused. The 2FA tests switch enforcement back on.

## Consequences

- With a single admin, losing both the phone and the recovery codes needs `php artisan identity:reset-two-factor <email>` on the server (audited as a console reset). The setup page asks staff to keep the codes safe.
