<?php

declare(strict_types=1);

namespace App\Modules\Billing\Filament\Resources\PaymentResource\Pages;

use App\Modules\Billing\Filament\Exports\PaymentExporter;
use App\Modules\Billing\Filament\Resources\PaymentResource;
use Filament\Resources\Pages\ListRecords;

final class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [PaymentResource::recordAction(), PaymentExporter::action()];
    }
}
