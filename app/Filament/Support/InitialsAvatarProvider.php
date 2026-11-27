<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Staff avatars drawn locally as an SVG with initials. Filament's default sends user
 * names to ui-avatars.com, which leaks personal data to a third party (GDPR) and is
 * blocked by the Content-Security-Policy anyway.
 */
final class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $name = Filament::getNameForDefaultAvatar($record);
        $initials = mb_strtoupper(collect(preg_split('/\s+/u', trim($name)) ?: [])
            ->filter()->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->implode(''));

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="#0f766e"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="#fff" font-family="system-ui,sans-serif" font-size="26">'
            .htmlspecialchars($initials, ENT_XML1).'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
