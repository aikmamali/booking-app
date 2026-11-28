<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identity\Enums\Locale;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\Totp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<model-property<User>, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => '+3069'.fake()->unique()->numerify('########'),
            'locale' => Locale::Greek,
            'checkin_token' => User::newCheckinToken(),
            'terms_accepted_version' => '2026-11-draft',
            'terms_accepted_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function customer(): static
    {
        return $this->withRole(Role::Customer);
    }

    public function instructor(): static
    {
        return $this->withRole(Role::Instructor);
    }

    public function admin(): static
    {
        return $this->withRole(Role::Admin);
    }

    /**
     * ID-07: 2FA already set up, with known recovery codes "aaaaa-11111" … "aaaaa-88888".
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (): array => [
            'two_factor_secret' => (new Totp)->newSecret(),
            'two_factor_recovery_codes' => array_map(fn (int $i): string => 'aaaaa-'.str_repeat((string) $i, 5), range(1, 8)),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function withRole(Role $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role->value));
    }
}
