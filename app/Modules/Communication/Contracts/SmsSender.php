<?php

declare(strict_types=1);

namespace App\Modules\Communication\Contracts;

/**
 * Sends a text message (spec 8.1). The provider (a Greek SMS gateway with a named sender)
 * sits behind this interface so it can change without touching the domain.
 */
interface SmsSender
{
    /**
     * @param  string  $to  E.164 number
     */
    public function send(string $to, string $message): void;
}
