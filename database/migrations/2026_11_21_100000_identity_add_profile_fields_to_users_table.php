<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('name');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name', 100)->after('id');
            $table->string('last_name', 100)->after('first_name');
            // E.164; nullable because staff and anonymised accounts may have none.
            $table->string('phone', 16)->nullable()->after('email');
            $table->string('phone_alt', 16)->nullable()->after('phone');
            $table->string('vat_number', 20)->nullable()->after('phone_alt');
            $table->string('locale', 5)->default('el')->after('vat_number');
            $table->string('checkin_token', 64)->unique()->after('locale');
            $table->timestamp('marketing_consent_at')->nullable()->after('checkin_token');
            $table->string('terms_accepted_version', 32)->nullable()->after('marketing_consent_at');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_accepted_version');
            $table->timestamp('last_login_at')->nullable()->after('terms_accepted_at');
            $table->timestamp('anonymized_at')->nullable()->after('last_login_at');

            $table->index(['last_name', 'first_name']);
        });

        Schema::create('invitation_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_tokens');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['last_name', 'first_name']);
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'phone_alt', 'vat_number', 'locale', 'checkin_token',
                'marketing_consent_at', 'terms_accepted_version', 'terms_accepted_at', 'last_login_at', 'anonymized_at',
            ]);
            $table->string('name')->after('id');
        });
    }
};
