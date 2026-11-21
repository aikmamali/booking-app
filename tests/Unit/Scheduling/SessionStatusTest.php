<?php

declare(strict_types=1);

use App\Modules\Scheduling\Enums\SessionStatus;

it('only lets a scheduled session be cancelled', function (SessionStatus $from, SessionStatus $to, bool $allowed): void {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    [SessionStatus::Scheduled, SessionStatus::Cancelled, true],
    [SessionStatus::Scheduled, SessionStatus::Scheduled, false],
    [SessionStatus::Cancelled, SessionStatus::Scheduled, false],
    [SessionStatus::Cancelled, SessionStatus::Cancelled, false],
]);
