<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_receipt_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('direction');
            $table->string('category')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('occurred_on');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique('service_receipt_id');
            $table->index(['company_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_entries');
    }
};
