<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->json('name');
            // Phase 1 sells credit packs only; unlimited packs were left out (spec section 14).
            $table->decimal('credits', 6, 2);
            $table->unsignedSmallInteger('validity_days');
            $table->unsignedInteger('price_cents');
            $table->unsignedTinyInteger('vat_rate');
            $table->boolean('is_purchasable_online')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('package_service', function (Blueprint $table): void {
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['package_id', 'service_id']);
        });

        Schema::create('memberships', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 16);
            $table->date('frozen_until')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            // Snapshot of the package terms at purchase time (spec 6.2).
            $table->json('package_name');
            $table->decimal('credits', 6, 2);
            $table->unsignedInteger('price_cents');
            $table->unsignedTinyInteger('vat_rate');
            $table->unsignedSmallInteger('validity_days');
            $table->json('service_ids');
            $table->foreignId('sold_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expiry_warned_at')->nullable();
            $table->timestamp('low_balance_warned_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'ends_on']);
            $table->index(['status', 'ends_on']);
        });

        Schema::create('credit_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('membership_id')->constrained()->restrictOnDelete();
            // Constrained to bookings once the Booking module exists (step 4).
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->string('type', 16);
            $table->decimal('amount', 6, 2);
            $table->decimal('balance_after', 6, 2);
            $table->string('reason', 255)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            // Append-only: no updated_at.
            $table->timestamp('created_at');

            $table->index(['membership_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('package_service');
        Schema::dropIfExists('packages');
    }
};
