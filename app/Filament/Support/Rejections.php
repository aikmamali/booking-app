<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Shared\Exceptions\UserFacingException;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Throwable;

/**
 * Runs an Action call from a Filament action and turns business rejections into a danger
 * notification, keeping the modal open.
 */
final class Rejections
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function run(Closure $callback, Action|TableAction $action): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            if (! $e instanceof UserFacingException) {
                throw $e;
            }

            Notification::make()->danger()->title($e->translated())->send();
            $action->halt();

            throw $e;
        }
    }
}
