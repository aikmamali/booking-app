<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RP-03: trainer notes about a customer, optionally tied to one booking (ADR 0011).
 * Instructors get limited admin access: their own classes and notes.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = [
        'view_any_note', 'view_note', 'create_note', 'update_note', 'delete_note',
        // Notes of every customer, and editing other authors' notes (admin).
        'manage_all_notes',
        // The instructor's own classes and participants (Booking module page).
        'page_MyClasses',
    ];

    /** @var list<string> */
    private array $instructor = ['view_any_note', 'view_note', 'create_note', 'update_note', 'delete_note', 'page_MyClasses'];

    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('visible_to_customer')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
        });

        $this->grant();
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', $this->permissions)->delete();
        app()['cache']->forget(config('permission.cache.key'));
        Schema::dropIfExists('notes');
    }

    private function grant(): void
    {
        $now = now();
        DB::table('permissions')->insert(array_map(
            fn (string $name): array => ['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            $this->permissions,
        ));

        $roleId = DB::table('roles')->where('name', 'instructor')->value('id');
        $permissionIds = DB::table('permissions')->whereIn('name', $this->instructor)->pluck('id');

        DB::table('role_has_permissions')->insert($permissionIds->map(
            fn (int $id): array => ['permission_id' => $id, 'role_id' => $roleId],
        )->all());

        app()['cache']->forget(config('permission.cache.key'));
    }
};
