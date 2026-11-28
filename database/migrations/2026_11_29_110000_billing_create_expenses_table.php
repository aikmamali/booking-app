<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = ['view_any_expense', 'view_expense', 'create_expense', 'update_expense', 'delete_expense'];

    public function up(): void
    {
        // BL-02: business expenses, for the monthly picture next to the payments.
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('category', 32);
            $table->unsignedInteger('amount_cents');
            $table->date('spent_on');
            $table->string('note', 500)->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['spent_on', 'category']);
        });

        $now = now();
        DB::table('permissions')->insert(array_map(
            fn (string $name): array => ['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            $this->permissions,
        ));
        app()['cache']->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', $this->permissions)->delete();
        Schema::dropIfExists('expenses');
    }
};
