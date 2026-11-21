<?php

declare(strict_types=1);

namespace App\Modules\Communication\Contracts;

use App\Modules\Communication\Models\Announcement;
use App\Shared\Clock;
use Illuminate\Support\Facades\Storage;

/**
 * Public read contract: the announcements published right now, for the customer home.
 */
final readonly class Announcements
{
    public function __construct(private Clock $clock) {}

    /**
     * @return list<array{title: string, body: ?string, image_url: ?string}>
     */
    public function current(): array
    {
        return array_values(Announcement::query()->publishedAt($this->clock->now())->orderBy('position')->get()
            ->map(fn (Announcement $a): array => [
                'title' => $a->title,
                'body' => $a->body,
                'image_url' => $a->image_path === null ? null : Storage::disk('public')->url($a->image_path),
            ])->all());
    }
}
