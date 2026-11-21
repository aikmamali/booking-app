<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identity\Enums\Locale;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
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

    public function withRole(Role $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role->value));
    }
}
