<?php

declare(strict_types=1);

namespace App\Modules\Communication\Enums;

/**
 * Transactional notifications of spec 8.1 (CM-01). Each has default channels, which the
 * customer may change (CM-02), and an editable template.
 */
enum NotificationType: string
{
    case BookingConfirmed = 'booking_confirmed';
    case BookingCancelled = 'booking_cancelled';
    case BookingRescheduled = 'booking_rescheduled';
    case BookingReminder = 'booking_reminder';
    case SessionCancelled = 'session_cancelled';
    case WaitlistPromoted = 'waitlist_promoted';
    case WaitlistOffered = 'waitlist_offered';
    case MembershipExpiring = 'membership_expiring';
    case LowBalance = 'low_balance';

    /**
     * @return list<Channel>
     */
    public function defaultChannels(): array
    {
        return match ($this) {
            self::SessionCancelled, self::WaitlistPromoted, self::WaitlistOffered => [Channel::Mail, Channel::Sms],
            default => [Channel::Mail],
        };
    }

    /**
     * Placeholders the template of this type can use, shown to staff editing it.
     *
     * @return list<string>
     */
    public function placeholders(): array
    {
        return match ($this) {
            self::MembershipExpiring, self::LowBalance => ['name', 'package', 'ends_on', 'balance'],
            self::WaitlistOffered => ['name', 'service', 'date', 'time', 'until'],
            default => ['name', 'service', 'date', 'time', 'instructor', 'reference', 'reason'],
        };
    }

    public function label(): string
    {
        return __('communication.types.'.$this->value);
    }
}
