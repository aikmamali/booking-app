<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Filament\Resources\ClassSessionResource\Pages;

use App\Modules\Scheduling\Actions\GenerateSessions;
use App\Modules\Scheduling\Filament\Resources\ClassSessionResource;
use App\Modules\Scheduling\Models\Schedule;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

final class ListClassSessions extends ListRecords
{
    protected static string $resource = ClassSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')->label(__('scheduling.actions.generate'))->icon('heroicon-o-arrow-path')
                ->authorize(fn (): bool => auth()->user()?->can('create', Schedule::class) ?? false)
                ->action(function (): void {
                    $count = app(GenerateSessions::class)->handle();

                    Notification::make()->success()->title(__('scheduling.notices.generated', ['count' => $count]))->send();
                }),
        ];
    }
}
