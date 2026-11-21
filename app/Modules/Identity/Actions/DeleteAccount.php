<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Events\UserDeleted;
use App\Modules\Identity\Models\User;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ID-04: deleting an account anonymises personal data. The row stays so that fiscal
 * documents keep their reference; other modules react to UserDeleted (e.g. cancel future
 * bookings). Pass $confirmPassword when the customer deletes their own account.
 */
final readonly class DeleteAccount
{
    public function __construct(private Clock $clock) {}

    public function handle(User $user, ?string $confirmPassword = null): void
    {
        if ($confirmPassword !== null && ! Hash::check($confirmPassword, $user->password)) {
            throw ValidationException::withMessages(['password' => __('validation.current_password')]);
        }

        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'first_name' => __('identity.anonymized.first_name', [], (string) config('app.locale')),
                'last_name' => '#'.$user->id,
                'email' => 'deleted-'.$user->id.'-'.Str::lower(Str::random(8)).'@anonymized.invalid',
                'phone' => null,
                'phone_alt' => null,
                'vat_number' => null,
                'password' => Str::random(64),
                'remember_token' => null,
                'checkin_token' => User::newCheckinToken(),
                'marketing_consent_at' => null,
                'email_verified_at' => null,
                'anonymized_at' => $this->clock->now(),
            ])->save();

            $user->syncRoles([]);

            DB::table('sessions')->where('user_id', $user->id)->delete();

            event(new UserDeleted($user->id));
        });
    }
}
