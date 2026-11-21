<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            // ISO-8601 weekday (1 = Monday) and wall-clock time in the business time zone.
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->unsignedSmallInteger('capacity');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index(['valid_from', 'valid_until']);
        });

        Schema::create('closures', function (Blueprint $table): void {
            $table->id();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason', 255);
            $table->timestamps();

            $table->index(['starts_on', 'ends_on']);
        });

        Schema::create('class_sessions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedSmallInteger('capacity');
            $table->string('status', 16)->default('scheduled');
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            // Set when staff edit this one session; schedule edits then leave it alone.
            $table->boolean('is_customized')->default(false);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['schedule_id', 'starts_at']);
            $table->index(['starts_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
        Schema::dropIfExists('closures');
        Schema::dropIfExists('schedules');
    }
};
