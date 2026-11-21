# ADR 0002: Phase 1 runs with customer, instructor and admin roles

- Status: accepted
- Date: 2026-11-21
- Decided by: Katerina

## Context

Spec section 2 defines four roles: customer, instructor, reception and admin. For the first phase the studio wants a single staff role with full access, adding reception later.

## Decision

- Roles in phase 1: `customer`, `instructor` and `admin` (`App\Modules\Identity\Enums\Role`).
- Only `admin` can enter the Filament panel. Admin is Filament Shield's super-admin role, so every Gate check passes for it.
- `instructor` exists so schedules and sessions can name an instructor (step 2). Instructors have no admin access yet; participant lists and check-in for instructors arrive together with reception.
- Policies check Shield-style permissions (`view_any_user`, `update_user`, …), not role names. Adding reception later means a new enum case, granting permissions in the Shield screen and allowing the role in `Role::canAccessAdminPanel()`. Policy code does not change.
- Staff accounts (instructor, admin) are managed by admins only, as the spec says. A future role with user permissions can manage customers only.
- Admins cannot remove their own admin role or anonymise their own account, so the panel cannot lose its last administrator by accident.

## Consequences

- Roles and the user permissions are created by a migration, so they exist in every environment, tests included.
- The first administrator is created with `php artisan identity:create-admin {email} {first_name} {last_name}`, which emails an invitation.
