<?php

declare(strict_types=1);

namespace App\Modules\Communication\Support;

use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Communication\Models\EmailTemplate;

/**
 * Subject and body of a notification in the recipient's language: the staff-edited
 * template where filled in, the default text otherwise. Placeholders look like :service.
 */
final class TemplateRenderer
{
    /**
     * @param  array<string, string>  $replace
     * @return array{subject: string, body: string}
     */
    public function render(NotificationType $type, string $locale, array $replace): array
    {
        $template = EmailTemplate::query()->where('type', $type)->first();

        return [
            'subject' => $this->fill($this->text($template, 'subject', $type, $locale), $replace),
            'body' => $this->fill($this->text($template, 'body', $type, $locale), $replace),
        ];
    }

    public function defaultText(NotificationType $type, string $part, string $locale): string
    {
        return (string) __("communication.templates.{$type->value}.{$part}", [], $locale);
    }

    private function text(?EmailTemplate $template, string $part, NotificationType $type, string $locale): string
    {
        $custom = $template?->getTranslation($part, $locale, false);

        return is_string($custom) && trim($custom) !== '' ? $custom : $this->defaultText($type, $part, $locale);
    }

    /**
     * @param  array<string, string>  $replace
     */
    private function fill(string $text, array $replace): string
    {
        uksort($replace, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return strtr($text, array_combine(array_map(fn (string $k): string => ':'.$k, array_keys($replace)), $replace));
    }
}
