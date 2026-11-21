<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('fixed_seat_id')->nullable();
            $table->string('status', 20);
            $table->decimal('credits_charged', 6, 2)->default(0);
            $table->string('source', 16);
            $table->boolean('remind')->default(true);
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('late_cancel')->default(false);
            $table->foreignId('rescheduled_to_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            // Only one active booking per customer and session (spec 6.1). NULL for inactive rows,
            // so cancelled or attended bookings do not block a new one.
            $table->unsignedTinyInteger('active_key')->nullable()->storedAs(
                "case when status in ('confirmed', 'pending_payment', 'pending_credits') then 1 else null end"
            );
            $table->unique(['user_id', 'class_session_id', 'active_key'], 'bookings_one_active_per_session');
            $table->index(['class_session_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::table('credit_transactions', function (Blueprint $table): void {
            $table->foreign('booking_id')->references('id')->on('bookings')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table): void {
            $table->dropForeign(['booking_id']);
        });
        Schema::dropIfExists('bookings');
    }
};
