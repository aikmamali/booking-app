<?php

declare(strict_types=1);

namespace App\Modules\Identity\Data;

use App\Modules\Identity\Enums\Locale;
use App\Shared\Validation\E164Phone;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Data;

final class RegisterCustomerData extends Data
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $email,
        public string $phone,
        public string $password,
        public bool $accept_terms,
        public bool $marketing_consent = false,
        public Locale $locale = Locale::Greek,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', new E164Phone],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'accept_terms' => ['accepted'],
            'marketing_consent' => ['boolean'],
            'locale' => ['required', Rule::enum(Locale::class)],
        ];
    }
}
