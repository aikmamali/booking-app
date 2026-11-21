<?php

declare(strict_types=1);

use App\Shared\Clock;
use App\Shared\Modules;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

/*
| Layering rules from CLAUDE.md and spec section 5. Each rule runs per module.
*/

/**
 * Models other modules may use directly: their module's public contract (spec 5.2).
 */
const PUBLIC_MODELS = [
    'App\Modules\Identity\Models\User',
    'App\Modules\Catalog\Models\Service',
    'App\Modules\Scheduling\Models\ClassSession',
];

const UI_NAMESPACES = ['Filament', 'Livewire', 'Illuminate\Http', 'Illuminate\Routing'];

foreach (Modules::ALL as $module) {
    $ns = Modules::namespace($module);

    arch("{$module}: has a final service provider")
        ->expect("{$ns}\\{$module}ServiceProvider")
        ->toBeFinal()
        ->toExtend(ServiceProvider::class);

    arch("{$module}: actions are final with a single handle() method")
        ->expect("{$ns}\\Actions")
        ->classes()
        ->toBeFinal()
        ->toHaveMethod('handle');

    arch("{$module}: domain layer does not depend on UI")
        ->expect(array_map(
            fn (string $layer): string => "{$ns}\\{$layer}",
            ['Models', 'Enums', 'Events', 'Exceptions', 'Rules', 'Data', 'Actions', 'Settings'],
        ))
        ->not->toUse([...UI_NAMESPACES, "{$ns}\\Filament", "{$ns}\\Livewire"]);

    arch("{$module}: booking rules do not read the clock themselves")
        ->expect("{$ns}\\Rules")
        ->not->toUse(['now', 'today', 'time', 'date', Clock::class]);

    arch("{$module}: domain events dispatch after commit")
        ->expect("{$ns}\\Events")
        ->classes()
        ->toImplement(ShouldDispatchAfterCommit::class);

    arch("{$module}: enums are backed enums")
        ->expect("{$ns}\\Enums")
        ->toBeStringBackedEnums();

    arch("{$module}: models are only used inside their module")
        ->expect("{$ns}\\Models")
        ->toOnlyBeUsedIn([$ns, 'Database', 'Tests'])
        ->ignoring(PUBLIC_MODELS);
}

arch('transactions are only opened inside actions')
    ->expect(DB::class)
    ->toOnlyBeUsedIn([
        ...array_map(fn (string $m): string => Modules::namespace($m).'\\Actions', Modules::ALL),
        'Database',
        'Tests',
    ]);
