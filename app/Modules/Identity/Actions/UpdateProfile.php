<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Data\UpdateProfileData;
use App\Modules\Identity\Models\User;
use App\Shared\PhoneNumber;
use Illuminate\Support\Facades\DB;

/**
 * ID-03. A new email address must be verified again.
 */
final class UpdateProfile
{
    public function handle(User $user, UpdateProfileData $data): User
    {
        $emailChanged = DB::transaction(function () use ($user, $data): bool {
            $user->fill([
                'first_name' => trim($data->first_name),
                'last_name' => trim($data->last_name),
                'email' => $data->email,
                'phone' => $this->normalisePhone($data->phone),
                'phone_alt' => $this->normalisePhone($data->phone_alt),
                'vat_number' => $data->vat_number,
                'locale' => $data->locale,
            ]);

            $emailChanged = $user->isDirty('email');

            if ($emailChanged) {
                $user->email_verified_at = null;
            }

            $user->save();

            return $emailChanged;
        });

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }

    private function normalisePhone(?string $phone): ?string
    {
        return $phone === null || trim($phone) === '' ? null : PhoneNumber::parse($phone)->e164;
    }
}
