<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->unsignedSmallInteger('duration_minutes')->default(60)->after('return_interval_months');
        });

        Schema::create('pets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('species')->nullable();
            $table->string('breed')->nullable();
            $table->string('size')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'customer_id', 'name']);
        });

        Schema::create('package_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('credits');
            $table->decimal('suggested_price', 12, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });

        Schema::create('pet_packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_offer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('total_credits');
            $table->decimal('price', 12, 2)->nullable();
            $table->string('payment_status')->default('pending');
            $table->date('purchased_at');
            $table->date('valid_until')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'pet_id', 'service_id']);
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('pet_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->foreignId('pet_package_id')->nullable()->after('service_id')->constrained('pet_packages')->nullOnDelete();
            $table->unsignedSmallInteger('duration_minutes')->default(60)->after('scheduled_at');
            $table->timestamp('ends_at')->nullable()->after('duration_minutes');
            $table->index(['company_id', 'scheduled_at', 'ends_at']);
        });

        Schema::create('package_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->timestamp('redeemed_at');
            $table->timestamps();
            $table->unique('appointment_id');
            $table->unique(['pet_package_id', 'appointment_id']);
        });

        DB::table('appointments')->orderBy('id')->each(function (object $appointment): void {
            DB::table('appointments')->where('id', $appointment->id)->update([
                'duration_minutes' => 60,
                'ends_at' => Carbon::parse($appointment->scheduled_at)->addMinutes(60),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_redemptions');
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'scheduled_at', 'ends_at']);
            $table->dropConstrainedForeignId('pet_package_id');
            $table->dropConstrainedForeignId('pet_id');
            $table->dropColumn(['duration_minutes', 'ends_at']);
        });
        Schema::dropIfExists('pet_packages');
        Schema::dropIfExists('package_offers');
        Schema::dropIfExists('pets');
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn('duration_minutes'));
    }
};
