<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pet_packages', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'pet_id', 'service_id']);
            $table->dropConstrainedForeignId('service_id');
        });

        Schema::table('package_offers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('service_id');
        });

        Schema::table('company_subscriptions', function (Blueprint $table): void {
            $table->dropColumn('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('package_offers', function (Blueprint $table): void {
            $table->foreignId('service_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });

        Schema::table('pet_packages', function (Blueprint $table): void {
            $table->foreignId('service_id')->nullable()->after('package_offer_id')->constrained()->nullOnDelete();
            $table->index(['company_id', 'pet_id', 'service_id']);
        });

        Schema::table('company_subscriptions', function (Blueprint $table): void {
            $table->timestamp('ends_at')->nullable()->after('starts_at');
        });
    }
};
