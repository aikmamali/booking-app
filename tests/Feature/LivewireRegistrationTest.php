<?php

declare(strict_types=1);

use App\Livewire\ModuleComponents;
use Livewire\Mechanisms\ComponentRegistry;

/*
| Regression: module components rendered as full pages worked on first load but every
| follow-up request failed (419), because Livewire could not resolve them by name.
*/
it('registers every module Livewire component under a resolvable name', function (): void {
    $components = ModuleComponents::discover();
    $registry = app(ComponentRegistry::class);

    expect($components)->toHaveKey('identity.login')->toHaveKey('identity.edit-profile-form');

    foreach ($components as $name => $class) {
        expect($registry->getName($class))->toBe($name)
            ->and($registry->getClass($name))->toBe($class);
    }
});

it('accepts a follow-up request from a module page rendered over HTTP', function (): void {
    $html = $this->get(route('app.login'))->assertOk()->getContent();

    preg_match('/wire:snapshot="([^"]+)"/', (string) $html, $snapshot);
    preg_match('/data-csrf="([^"]+)"/', (string) $html, $csrf);

    $this->postJson('/livewire/update', [
        '_token' => $csrf[1],
        'components' => [[
            'snapshot' => html_entity_decode($snapshot[1]),
            'updates' => ['email' => 'nobody@example.com', 'password' => 'wrong'],
            'calls' => [['path' => '', 'method' => 'login', 'params' => []]],
        ]],
    ], ['X-Livewire' => '1'])->assertOk();
});
