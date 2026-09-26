<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('business_breaks')->nullable()->after('appointment_slot_minutes');
            $table->timestamp('hours_configured_at')->nullable()->after('onboarding_completed_at');
            $table->timestamp('guide_viewed_at')->nullable()->after('hours_configured_at');
            $table->string('public_booking_token', 64)->nullable()->unique()->after('guide_viewed_at');
        });

        Schema::table('company_subscriptions', function (Blueprint $table) {
            $table->timestamp('ends_at')->nullable()->after('starts_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('owner')->after('is_super_admin');
        });

        Schema::table('pets', function (Blueprint $table) {
            $table->string('temperament')->nullable()->after('size');
            $table->string('coat')->nullable()->after('temperament');
            $table->string('allergies')->nullable()->after('coat');
            $table->decimal('weight_kg', 5, 2)->nullable()->after('allergies');
            $table->string('photo_path')->nullable()->after('weight_kg');
        });

        Schema::create('groomers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('groomer_id')->nullable()->after('pet_package_id')->constrained()->nullOnDelete();
            $table->string('recurrence_group', 64)->nullable()->after('status');
            $table->string('confirmation_token', 64)->nullable()->unique()->after('recurrence_group');
        });

        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('preferred_date');
            $table->string('preferred_time')->nullable();
            $table->string('status', 20)->default('waiting');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('groomer_id');
            $table->dropColumn(['recurrence_group', 'confirmation_token']);
        });

        Schema::dropIfExists('groomers');

        Schema::table('pets', function (Blueprint $table) {
            $table->dropColumn(['temperament', 'coat', 'allergies', 'weight_kg', 'photo_path']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('company_subscriptions', function (Blueprint $table) {
            $table->dropColumn('ends_at');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['business_breaks', 'hours_configured_at', 'guide_viewed_at', 'public_booking_token']);
        });
    }
};
