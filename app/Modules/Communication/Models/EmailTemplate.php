<?php

declare(strict_types=1);

namespace App\Modules\Communication\Models;

use App\Modules\Communication\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Staff-edited wording of a notification (CM-01). An empty translation falls back to the
 * default text in lang/{el,en}/communication.php.
 *
 * @property int $id
 * @property NotificationType $type
 */
class EmailTemplate extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['subject', 'body'];

    /** @var list<string> */
    protected $fillable = ['type', 'subject', 'body'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['type' => NotificationType::class];
    }
}
