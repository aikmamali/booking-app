<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Shield-style permissions for the catalog and scheduling resources (see ADR 0002).
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $subjects = ['service::category', 'service', 'room', 'schedule', 'closure', 'class::session'];

    /** @var list<string> */
    private array $abilities = ['view_any', 'view', 'create', 'update', 'delete', 'reorder'];

    public function up(): void
    {
        $now = now();
        $rows = [];

        foreach ($this->names() as $name) {
            $rows[] = ['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now];
        }

        DB::table('permissions')->insert($rows);
        app()['cache']->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', $this->names())->delete();
        app()['cache']->forget(config('permission.cache.key'));
    }

    /**
     * @return list<string>
     */
    private function names(): array
    {
        $names = [];

        foreach ($this->subjects as $subject) {
            foreach ($this->abilities as $ability) {
                $names[] = $ability.'_'.$subject;
            }
        }

        return $names;
    }
};
