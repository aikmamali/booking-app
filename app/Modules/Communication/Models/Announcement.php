<?php

declare(strict_types=1);

namespace App\Modules\Communication\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * News and banners on the customer home page (CM-04).
 *
 * @property int $id
 * @property string $title
 * @property ?string $body
 * @property ?string $image_path
 * @property CarbonImmutable $published_from
 * @property ?CarbonImmutable $published_until
 * @property int $position
 */
class Announcement extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['title', 'body'];

    /** @var list<string> */
    protected $fillable = ['title', 'body', 'image_path', 'published_from', 'published_until', 'position'];

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublishedAt(Builder $query, CarbonImmutable $now): Builder
    {
        return $query->where('published_from', '<=', $now)
            ->where(fn (Builder $q) => $q->whereNull('published_until')->orWhere('published_until', '>', $now));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_from' => 'immutable_datetime',
            'published_until' => 'immutable_datetime',
        ];
    }
}
