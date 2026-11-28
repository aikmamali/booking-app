<?php

declare(strict_types=1);

namespace App\Modules\Communication\Data;

use Spatie\LaravelData\Data;

final class NoteData extends Data
{
    public function __construct(
        public int $user_id,
        public string $body,
        public bool $visible_to_customer = false,
        public ?int $booking_id = null,
    ) {}
}
