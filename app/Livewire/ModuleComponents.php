<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Shared\Modules;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;
use ReflectionClass;

/**
 * Livewire only resolves components in App\Livewire by itself. Components inside modules
 * are registered as "<module>.<component>", e.g. identity.edit-profile-form, so that
 * follow-up requests can find them again.
 */
final class ModuleComponents
{
    public static function register(): void
    {
        foreach (self::discover() as $name => $class) {
            Livewire::component($name, $class);
        }
    }

    /**
     * @return array<string, class-string<Component>>
     */
    public static function discover(): array
    {
        $components = [];

        foreach (Modules::ALL as $module) {
            foreach (glob(Modules::path($module, 'Livewire').'/*.php') ?: [] as $file) {
                $class = Modules::namespace($module).'\\Livewire\\'.basename($file, '.php');

                if (self::isComponent($class)) {
                    $components[Str::kebab($module).'.'.Str::kebab(class_basename($class))] = $class;
                }
            }
        }

        return $components;
    }

    /**
     * @phpstan-assert-if-true class-string<Component> $class
     */
    private static function isComponent(string $class): bool
    {
        return class_exists($class)
            && is_subclass_of($class, Component::class)
            && ! (new ReflectionClass($class))->isAbstract();
    }
}
