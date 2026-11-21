<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Data\RegisterCustomerData;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Settings\IdentitySettings;
use App\Shared\Clock;
use App\Shared\PhoneNumber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

/**
 * ID-01: self-registration. Records the accepted terms version and time; the Registered
 * event sends the email verification link.
 */
final readonly class RegisterCustomer
{
    public function __construct(
        private IdentitySettings $settings,
        private Clock $clock,
    ) {}

    public function handle(RegisterCustomerData $data): User
    {
        $now = $this->clock->now();

        $user = DB::transaction(function () use ($data, $now): User {
            $user = User::create([
                'first_name' => trim($data->first_name),
                'last_name' => trim($data->last_name),
                'email' => $data->email,
                'phone' => PhoneNumber::parse($data->phone)->e164,
                'locale' => $data->locale,
                'password' => $data->password,
                'checkin_token' => User::newCheckinToken(),
                'terms_accepted_version' => $this->settings->terms_version,
                'terms_accepted_at' => $now,
                'marketing_consent_at' => $data->marketing_consent ? $now : null,
            ]);

            $user->assignRole(Role::Customer->value);

            return $user;
        });

        event(new Registered($user));

        return $user;
    }
}
