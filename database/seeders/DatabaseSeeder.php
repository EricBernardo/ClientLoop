<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Plan::firstOrCreate(['name' => 'Teste gratuito'], ['contact_limit' => 500, 'task_limit' => 1000, 'is_default' => true]);

        $this->call(DemoClinicSeeder::class);
    }
}
