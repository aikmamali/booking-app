<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * RP-02: every admin list exports to Excel (queued, spec 3.10). Shared notification text
 * and the action that offers xlsx and csv.
 */
abstract class BaseExporter extends Exporter
{
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = __('reporting.export_done', ['rows' => number_format($export->successful_rows)]);
        $failed = $export->getFailedRowsCount();

        return $failed > 0 ? $body.' '.__('reporting.export_failed', ['rows' => number_format($failed)]) : $body;
    }

    /**
     * @return list<ExportFormat>
     */
    public function getFormats(): array
    {
        return [ExportFormat::Xlsx, ExportFormat::Csv];
    }

    public static function action(): ExportAction
    {
        return ExportAction::make()->exporter(static::class)->label(__('reporting.export'))->icon('heroicon-o-arrow-down-tray');
    }
}
