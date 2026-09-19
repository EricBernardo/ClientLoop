<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_runs', fn (Blueprint $table) => $table->json('mapping')->nullable()->after('path'));
    }

    public function down(): void
    {
        Schema::table('import_runs', fn (Blueprint $table) => $table->dropColumn('mapping'));
    }
};
