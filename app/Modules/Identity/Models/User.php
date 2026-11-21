<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\Locale;
use App\Modules\Identity\Enums\Role;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * One table for customers and staff; the role comes from spatie/laravel-permission.
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property ?string $phone
 * @property ?string $phone_alt
 * @property ?string $vat_number
 * @property Locale $locale
 * @property string $checkin_token
 * @property ?CarbonImmutable $email_verified_at
 * @property ?CarbonImmutable $marketing_consent_at
 * @property ?string $terms_accepted_version
 * @property ?CarbonImmutable $terms_accepted_at
 * @property ?CarbonImmutable $last_login_at
 * @property ?CarbonImmutable $anonymized_at
 */
#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use LogsActivity;
    use Notifiable;

    /** @var list<string> */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_alt',
        'vat_number',
        'locale',
        'password',
        'checkin_token',
        'marketing_consent_at',
        'terms_accepted_version',
        'terms_accepted_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
        'checkin_token',
    ];

    public static function newCheckinToken(): string
    {
        return bin2hex(random_bytes(20));
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getFilamentName(): string
    {
        return $this->fullName();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->primaryRole()?->canAccessAdminPanel() ?? false;
    }

    public function primaryRole(): ?Role
    {
        $name = $this->getRoleNames()->first();

        return is_string($name) ? Role::tryFrom($name) : null;
    }

    public function isAnonymized(): bool
    {
        return $this->anonymized_at !== null;
    }

    public function preferredLocale(): string
    {
        return $this->locale->value;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'email', 'phone', 'phone_alt', 'vat_number', 'locale', 'anonymized_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'locale' => Locale::class,
            'marketing_consent_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'last_login_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }
}
