<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table): void {
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('ready_alerted_at')->nullable();
            $table->timestamp('unpaid_delivery_alerted_at')->nullable();
        });

        DB::table('service_orders')->whereNull('status_changed_at')->update([
            'status_changed_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table): void {
            $table->dropColumn(['status_changed_at', 'ready_alerted_at', 'unpaid_delivery_alerted_at']);
        });
    }
};
