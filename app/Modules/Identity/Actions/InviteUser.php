<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Data\InviteUserData;
use App\Modules\Identity\Models\User;
use App\Shared\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ID-05: staff create an account and the person sets their own password from the
 * invitation email.
 */
final readonly class InviteUser
{
    public function __construct(private SendInvitation $sendInvitation) {}

    public function handle(InviteUserData $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'first_name' => trim($data->first_name),
                'last_name' => trim($data->last_name),
                'email' => $data->email,
                'phone' => $data->phone === null || $data->phone === '' ? null : PhoneNumber::parse($data->phone)->e164,
                'locale' => $data->locale,
                // Unknown random password until the invitation is accepted.
                'password' => Str::random(64),
                'checkin_token' => User::newCheckinToken(),
            ]);

            $user->assignRole($data->role->value);

            return $user;
        });

        $this->sendInvitation->handle($user);

        return $user;
    }
}
