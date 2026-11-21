<?php

declare(strict_types=1);

use App\Shared\Credits;
use App\Shared\Money;

arch('every file declares strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('no debugging leftovers')
    ->preset()
    ->php();

arch('no insecure functions')
    ->preset()
    ->security();

arch('shared kernel does not depend on modules or UI')
    ->expect('App\Shared')
    ->not->toUse(['App\Modules', 'Filament', 'Livewire', 'Illuminate\Http']);

arch('value objects are final and readonly')
    ->expect([Money::class, Credits::class])
    ->toBeFinal()
    ->toBeReadonly();
