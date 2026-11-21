<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Support\Rejections;
use App\Modules\Scheduling\Actions\SaveSchedule;
use App\Modules\Scheduling\Filament\Resources\ScheduleResource;
use App\Modules\Scheduling\Models\Schedule;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

final class ManageSchedules extends ManageRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data, CreateAction $action): Model => Rejections::run(
                fn (): Schedule => app(SaveSchedule::class)->handle(null, ScheduleResource::toData($data)),
                $action,
            )),
        ];
    }
}
