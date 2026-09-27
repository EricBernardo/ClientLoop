<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'onboarding_completed_at',
                'setup_wizard_completed_at',
                'hours_configured_at',
                'guide_viewed_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamp('setup_wizard_completed_at')->nullable();
            $table->timestamp('hours_configured_at')->nullable();
            $table->timestamp('guide_viewed_at')->nullable();
        });
    }
};
