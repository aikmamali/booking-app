<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> must match App\Modules\Communication\Enums\NotificationType */
    private array $types = [
        'booking_confirmed', 'booking_cancelled', 'booking_rescheduled', 'booking_reminder', 'session_cancelled',
        'waitlist_promoted', 'waitlist_offered', 'membership_expiring', 'low_balance',
    ];

    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('channel', 16);
            $table->boolean('enabled');
            $table->timestamps();

            $table->unique(['user_id', 'type', 'channel']);
        });

        // One row per notification type; null subject/body means "use the default text".
        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 32)->unique();
            $table->json('subject')->nullable();
            $table->json('body')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->json('title');
            $table->json('body')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamp('published_from');
            $table->timestamp('published_until')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['published_from', 'published_until']);
        });

        $now = now();
        DB::table('email_templates')->insert(array_map(
            fn (string $type): array => ['type' => $type, 'created_at' => $now, 'updated_at' => $now],
            $this->types,
        ));

        $permissions = ['view_any_announcement', 'view_announcement', 'create_announcement', 'update_announcement',
            'delete_announcement', 'reorder_announcement', 'view_any_email::template', 'update_email::template'];
        DB::table('permissions')->insert(array_map(
            fn (string $name): array => ['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            $permissions,
        ));
        app()['cache']->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        DB::table('permissions')->where('name', 'like', '%announcement')->orWhere('name', 'like', '%email::template')->delete();
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('notification_preferences');
    }
};
