<?php

declare(strict_types=1);

namespace App\Modules\Identity\Data;

use App\Modules\Identity\Enums\Locale;
use App\Shared\Validation\E164Phone;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class UpdateProfileData extends Data
{
    public function __construct(
        public int $user_id,
        public string $first_name,
        public string $last_name,
        public string $email,
        public ?string $phone,
        public ?string $phone_alt,
        public ?string $vat_number,
        public Locale $locale,
        public bool $phone_required = true,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(ValidationContext $context): array
    {
        $phoneRequired = (bool) ($context->payload['phone_required'] ?? true);

        return [
            'user_id' => ['required', 'integer'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($context->payload['user_id'] ?? null),
            ],
            'phone' => [$phoneRequired ? 'required' : 'nullable', 'string', new E164Phone],
            'phone_alt' => ['nullable', 'string', new E164Phone],
            'vat_number' => ['nullable', 'string', 'regex:/^\d{9}$/'],
            'locale' => ['required', Rule::enum(Locale::class)],
        ];
    }
}
