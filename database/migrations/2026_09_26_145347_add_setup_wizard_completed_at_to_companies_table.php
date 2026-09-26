<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('setup_wizard_completed_at')->nullable()->after('onboarding_completed_at');
        });

        DB::table('companies')->whereNull('setup_wizard_completed_at')->update([
            'setup_wizard_completed_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('setup_wizard_completed_at');
        });
    }
};
