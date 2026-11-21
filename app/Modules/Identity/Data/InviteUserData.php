<?php

declare(strict_types=1);

namespace App\Modules\Identity\Data;

use App\Modules\Identity\Enums\Locale;
use App\Modules\Identity\Enums\Role;
use App\Shared\Validation\E164Phone;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class InviteUserData extends Data
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $email,
        public ?string $phone,
        public Role $role,
        public Locale $locale = Locale::Greek,
    ) {}

    /**
     * Customers need a phone (ID-01); staff accounts may be created without one.
     *
     * @return array<string, mixed>
     */
    public static function rules(ValidationContext $context): array
    {
        $isCustomer = ($context->payload['role'] ?? null) === Role::Customer->value
            || ($context->payload['role'] ?? null) === Role::Customer;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => [$isCustomer ? 'required' : 'nullable', 'string', new E164Phone],
            'role' => ['required', Rule::enum(Role::class)],
            'locale' => ['required', Rule::enum(Locale::class)],
        ];
    }
}
