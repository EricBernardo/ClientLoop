<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->string('status');
            $table->date('issued_on');
            $table->date('paid_on')->nullable();
            $table->timestamps();
            $table->unique('service_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_receipts');
    }
};
