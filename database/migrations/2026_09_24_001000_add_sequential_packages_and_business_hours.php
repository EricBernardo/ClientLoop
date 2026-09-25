<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->json('business_days')->nullable()->after('reactivation_months');
            $table->unsignedTinyInteger('business_starts_at_hour')->default(9)->after('business_days');
            $table->unsignedTinyInteger('business_ends_at_hour')->default(17)->after('business_starts_at_hour');
            $table->unsignedTinyInteger('appointment_slot_minutes')->default(60)->after('business_ends_at_hour');
        });

        Schema::table('package_offers', function (Blueprint $table): void {
            $table->foreignId('service_id')->nullable()->change();
        });

        Schema::table('pet_packages', function (Blueprint $table): void {
            $table->foreignId('service_id')->nullable()->change();
        });

        Schema::create('package_offer_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->timestamps();
            $table->unique(['package_offer_id', 'position']);
        });

        Schema::create('pet_package_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_name');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->unsignedSmallInteger('position');
            $table->timestamps();
            $table->unique(['pet_package_id', 'position']);
        });

        Schema::table('package_redemptions', function (Blueprint $table): void {
            $table->foreignId('pet_package_item_id')->nullable()->after('pet_package_id')->constrained()->nullOnDelete();
            $table->unique('pet_package_item_id');
        });

        DB::table('companies')->whereNull('business_days')->update(['business_days' => json_encode([1, 2, 3, 4, 5, 6])]);

        DB::table('package_offers')->orderBy('id')->each(function (object $offer): void {
            for ($position = 1; $position <= $offer->credits; $position++) {
                DB::table('package_offer_items')->insert([
                    'company_id' => $offer->company_id,
                    'package_offer_id' => $offer->id,
                    'service_id' => $offer->service_id,
                    'position' => $position,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        DB::table('pet_packages')->orderBy('id')->each(function (object $package): void {
            $service = DB::table('services')->where('id', $package->service_id)->first();

            for ($position = 1; $position <= $package->total_credits; $position++) {
                DB::table('pet_package_items')->insert([
                    'company_id' => $package->company_id,
                    'pet_package_id' => $package->id,
                    'service_id' => $package->service_id,
                    'service_name' => $service?->name ?? 'Serviço removido',
                    'duration_minutes' => $service?->duration_minutes ?? 60,
                    'position' => $position,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $items = DB::table('pet_package_items')->where('pet_package_id', $package->id)->orderBy('position')->pluck('id')->all();
            DB::table('package_redemptions')->where('pet_package_id', $package->id)->orderBy('redeemed_at')->orderBy('id')->get()->each(function (object $redemption, int $index) use ($items): void {
                if (isset($items[$index])) {
                    DB::table('package_redemptions')->where('id', $redemption->id)->update(['pet_package_item_id' => $items[$index]]);
                }
            });
        });
    }

    public function down(): void
    {
        Schema::table('package_redemptions', function (Blueprint $table): void {
            $table->dropUnique(['pet_package_item_id']);
            $table->dropConstrainedForeignId('pet_package_item_id');
        });
        Schema::dropIfExists('pet_package_items');
        Schema::dropIfExists('package_offer_items');
        Schema::table('pet_packages', fn (Blueprint $table) => $table->foreignId('service_id')->nullable(false)->change());
        Schema::table('package_offers', fn (Blueprint $table) => $table->foreignId('service_id')->nullable(false)->change());
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn(['business_days', 'business_starts_at_hour', 'business_ends_at_hour', 'appointment_slot_minutes']);
        });
    }
};
