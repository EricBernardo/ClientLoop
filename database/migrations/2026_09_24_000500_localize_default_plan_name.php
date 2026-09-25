<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyPlan = DB::table('plans')->where('name', 'Trial')->first();

        if (! $legacyPlan) {
            return;
        }

        $localizedPlan = DB::table('plans')->where('name', 'Teste gratuito')->first();

        if (! $localizedPlan) {
            DB::table('plans')->where('id', $legacyPlan->id)->update(['name' => 'Teste gratuito']);

            return;
        }

        DB::table('company_subscriptions')->where('plan_id', $legacyPlan->id)->update(['plan_id' => $localizedPlan->id]);
        DB::table('plans')->where('id', $legacyPlan->id)->delete();
    }

    public function down(): void
    {
        // A alteração consolida dois possíveis planos iniciais e não deve ser revertida automaticamente.
    }
};
