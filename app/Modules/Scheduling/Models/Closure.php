<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * A closed day or period (SC-04). Dates are local to the business time zone, inclusive.
 *
 * @property int $id
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property string $reason
 */
class Closure extends Model
{
    /** @var list<string> */
    protected $fillable = ['starts_on', 'ends_on', 'reason'];

    public function covers(CarbonImmutable $date): bool
    {
        $day = $date->format('Y-m-d');

        return $day >= $this->starts_on->format('Y-m-d') && $day <= $this->ends_on->format('Y-m-d');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
        ];
    }
}
