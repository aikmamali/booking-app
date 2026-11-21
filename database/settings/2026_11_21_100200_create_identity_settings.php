<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('identity.session_lifetime_minutes', 120);
        $this->migrator->add('identity.terms_version', '2026-11-draft');
    }
};
