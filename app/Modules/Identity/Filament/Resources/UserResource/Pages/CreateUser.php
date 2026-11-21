<?php

declare(strict_types=1);

namespace App\Modules\Identity\Filament\Resources\UserResource\Pages;

use App\Modules\Identity\Actions\InviteUser;
use App\Modules\Identity\Data\InviteUserData;
use App\Modules\Identity\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['email'] = mb_strtolower(trim((string) $data['email']));

        return app(InviteUser::class)->handle(InviteUserData::from($data));
    }

    protected function getCreatedNotificationTitle(): string
    {
        return __('identity.admin.invited');
    }
}
