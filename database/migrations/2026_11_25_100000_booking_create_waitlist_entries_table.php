<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_entries', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_session_id')->constrained()->restrictOnDelete();
            $table->string('status', 16);
            $table->timestamp('offered_until')->nullable();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            // One live entry per customer and session (spec 6.1); leaving lets them join again.
            $table->unsignedTinyInteger('active_key')->nullable()->storedAs(
                "case when status in ('waiting', 'offered') then 1 else null end"
            );
            $table->unique(['user_id', 'class_session_id', 'active_key'], 'waitlist_one_live_per_session');
            $table->index(['class_session_id', 'status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
