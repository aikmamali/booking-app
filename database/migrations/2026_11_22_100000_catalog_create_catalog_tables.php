<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table): void {
            $table->id();
            $table->json('name');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('service_categories')->restrictOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('default_capacity');
            $table->decimal('credit_cost', 6, 2);
            $table->unsignedInteger('drop_in_price_cents')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->json('policy_overrides')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'position']);
        });

        Schema::create('rooms', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
    }
};
