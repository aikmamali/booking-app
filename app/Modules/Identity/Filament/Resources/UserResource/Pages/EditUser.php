<?php

declare(strict_types=1);

namespace App\Modules\Identity\Filament\Resources\UserResource\Pages;

use App\Modules\Identity\Actions\ChangeUserRole;
use App\Modules\Identity\Actions\UpdateProfile;
use App\Modules\Identity\Data\UpdateProfileData;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Filament\Resources\UserResource;
use App\Modules\Identity\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->getRecord();
        $data['role'] = $user->primaryRole()?->value;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        /** @var User $actor */
        $actor = auth()->user();
        $role = Role::from((string) $data['role']);

        app(ChangeUserRole::class)->handle($actor, $record, $role);

        return app(UpdateProfile::class)->handle($record, UpdateProfileData::from([
            ...$data,
            'user_id' => $record->id,
            'email' => mb_strtolower(trim((string) $data['email'])),
            'phone_required' => $role === Role::Customer,
        ]));
    }
}
