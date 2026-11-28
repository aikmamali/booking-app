<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CM-03: bulk email / SMS to a filtered audience. The recipients are fixed when the
 * message is queued, so a retry never reaches someone new or anyone twice.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = ['view_any_broadcast', 'view_broadcast', 'create_broadcast'];

    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('subject', 150);
            $table->text('body');
            $table->json('channels');
            $table->json('audience');
            $table->string('status', 16);
            $table->unsignedInteger('recipients_count');
            $table->unsignedInteger('sent_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('broadcast_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('broadcast_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at')->nullable();

            $table->unique(['broadcast_id', 'user_id']);
            $table->index(['broadcast_id', 'sent_at']);
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
        app()['cache']->forget(config('permission.cache.key'));
        Schema::dropIfExists('broadcast_recipients');
        Schema::dropIfExists('broadcasts');
    }
};
