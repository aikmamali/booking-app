<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Roles must exist in every environment (production, tests), so they are created here
 * rather than in a seeder. Permissions follow Filament Shield naming so a future role
 * (reception) can be granted them from the Shield screen.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $roles = ['customer', 'instructor', 'admin'];

    /** @var list<string> */
    private array $permissions = [
        'view_any_user', 'view_user', 'create_user', 'update_user', 'delete_user',
    ];

    public function up(): void
    {
        $now = now();
        $guard = 'web';

        DB::table('roles')->insert(array_map(
            fn (string $name): array => ['name' => $name, 'guard_name' => $guard, 'created_at' => $now, 'updated_at' => $now],
            $this->roles,
        ));

        DB::table('permissions')->insert(array_map(
            fn (string $name): array => ['name' => $name, 'guard_name' => $guard, 'created_at' => $now, 'updated_at' => $now],
            $this->permissions,
        ));

        app()['cache']->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', $this->permissions)->delete();
        DB::table('roles')->whereIn('name', $this->roles)->delete();

        app()['cache']->forget(config('permission.cache.key'));
    }
};
