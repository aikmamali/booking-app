<?php

declare(strict_types=1);

namespace App\Modules\Communication\Actions;

use App\Modules\Communication\Models\EmailTemplate;

/**
 * CM-01: staff edit a notification's wording. Empty translations fall back to the default.
 */
final class SaveEmailTemplate
{
    /**
     * @param  array<string, string|null>  $subject  by locale
     * @param  array<string, string|null>  $body  by locale
     */
    public function handle(EmailTemplate $template, array $subject, array $body): EmailTemplate
    {
        $template->setTranslations('subject', array_filter($subject, filled(...)));
        $template->setTranslations('body', array_filter($body, filled(...)));
        $template->save();

        return $template;
    }
}
