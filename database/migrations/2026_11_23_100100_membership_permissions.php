<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Shield-style permissions for packages and memberships, plus the custom permissions for
 * selling a package (MB-02) and correcting credits by hand (MB-07, admin only per spec 2).
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $names = [
        'view_any_package', 'view_package', 'create_package', 'update_package', 'delete_package', 'reorder_package',
        'view_any_membership', 'view_membership', 'create_membership', 'update_membership',
        'sell_package', 'adjust_credits',
    ];

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->insert(array_map(
            fn (string $name): array => ['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            $this->names,
        ));
        app()['cache']->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', $this->names)->delete();
        app()['cache']->forget(config('permission.cache.key'));
    }
};
