<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FS-01 to FS-04: seasons and fixed weekly seats. A fixed-seat booking without credits is
 * held as pending_credits until `hold_until` (spec 7.1, 7.4).
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = [
        'view_any_season', 'view_season', 'create_season', 'update_season', 'delete_season',
        'view_any_fixed::seat', 'view_fixed::seat', 'create_fixed::seat', 'update_fixed::seat',
    ];

    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();
        });

        Schema::create('fixed_seats', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('season_id')->constrained()->restrictOnDelete();
            $table->string('status', 16);
            $table->unsignedSmallInteger('consecutive_cancellations')->default(0);
            $table->string('revoked_reason', 255)->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['schedule_id', 'season_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('hold_until')->nullable()->after('status');
            $table->foreign('fixed_seat_id')->references('id')->on('fixed_seats')->nullOnDelete();
            $table->index(['status', 'hold_until']);
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

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropForeign(['fixed_seat_id']);
            $table->dropIndex(['status', 'hold_until']);
            $table->dropColumn('hold_until');
        });
        Schema::dropIfExists('fixed_seats');
        Schema::dropIfExists('seasons');
    }
};
