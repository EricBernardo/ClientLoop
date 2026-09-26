<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usage_records', function (Blueprint $table) {
            $table->timestamp('task_quota_notified_at')->nullable()->after('tasks_count');
        });
    }

    public function down(): void
    {
        Schema::table('usage_records', function (Blueprint $table) {
            $table->dropColumn('task_quota_notified_at');
        });
    }
};
