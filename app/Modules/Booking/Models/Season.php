<?php

declare(strict_types=1);

namespace App\Modules\Booking\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * FS-01: e.g. 31/8/2026 to 30/6/2027. Fixed seats last at most one season.
 *
 * @property int $id
 * @property string $name
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 */
class Season extends Model
{
    /** @var list<string> */
    protected $fillable = ['name', 'starts_on', 'ends_on'];

    public function label(): string
    {
        return $this->name.' ('.$this->starts_on->format('d/m/Y').' – '.$this->ends_on->format('d/m/Y').')';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['starts_on' => 'immutable_date', 'ends_on' => 'immutable_date'];
    }
}
