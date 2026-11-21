<?php

declare(strict_types=1);

namespace App\Modules\Identity\Enums;

/**
 * Roles of spec section 2. Phase 1 runs with customer, instructor and admin only (ADR 0002);
 * reception is added later as a new case with its own permissions.
 */
enum Role: string
{
    case Customer = 'customer';

    /** Selectable on schedules and sessions. No admin panel access yet. */
    case Instructor = 'instructor';

    /** Full access. Bypasses permission checks via the Shield super-admin gate. */
    case Admin = 'admin';

    public function canAccessAdminPanel(): bool
    {
        return $this === self::Admin;
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }

    public function label(): string
    {
        return __('identity.roles.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }
}
