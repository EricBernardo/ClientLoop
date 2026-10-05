<?php

use App\Models\Company;
use App\Services\DefaultMessageTemplateService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('vehicle_id')->nullable()->after('pet_id')->constrained()->nullOnDelete();
        });

        Schema::table('waitlist_entries', function (Blueprint $table): void {
            $table->foreignId('pet_id')->nullable()->change();
            $table->foreignId('vehicle_id')->nullable()->after('pet_id')->constrained()->nullOnDelete();
        });

        Company::query()->where('vertical', 'automotive')->each(function (Company $company): void {
            app(DefaultMessageTemplateService::class)->provision($company);
        });
    }

    public function down(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vehicle_id');
            $table->foreignId('pet_id')->nullable(false)->change();
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vehicle_id');
        });
    }
};
