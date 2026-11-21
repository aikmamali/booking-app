<?php

declare(strict_types=1);

namespace App\Modules\Communication\Filament\Resources\EmailTemplateResource\Pages;

use App\Modules\Communication\Filament\Resources\EmailTemplateResource;
use Filament\Resources\Pages\ManageRecords;

final class ManageEmailTemplates extends ManageRecords
{
    protected static string $resource = EmailTemplateResource::class;
}
