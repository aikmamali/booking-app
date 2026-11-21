<?php

declare(strict_types=1);

namespace App\Modules\Communication\Actions;

use App\Modules\Communication\Models\Announcement;

/**
 * CM-04: news shown on the customer home page between two dates.
 */
final class SaveAnnouncement
{
    /**
     * @param  array<string, string|null>  $title  by locale
     * @param  array<string, string|null>  $body  by locale
     */
    public function handle(?Announcement $announcement, array $title, array $body, ?string $imagePath, string $publishedFrom, ?string $publishedUntil): Announcement
    {
        $announcement ??= new Announcement(['position' => (int) Announcement::query()->max('position') + 1]);
        $announcement->fill([
            'image_path' => $imagePath,
            'published_from' => $publishedFrom,
            'published_until' => $publishedUntil,
        ]);
        $announcement->setTranslations('title', array_filter($title, filled(...)));
        $announcement->setTranslations('body', array_filter($body, filled(...)));
        $announcement->save();

        return $announcement;
    }
}
