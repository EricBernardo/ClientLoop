<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_tasks', function (Blueprint $table): void {
            $table->string('cycle_key')->nullable()->after('type');
            $table->index(['company_id', 'customer_id', 'type', 'cycle_key'], 'contact_task_cycle_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('contact_tasks', function (Blueprint $table): void {
            $table->dropIndex('contact_task_cycle_lookup');
            $table->dropColumn('cycle_key');
        });
    }
};
