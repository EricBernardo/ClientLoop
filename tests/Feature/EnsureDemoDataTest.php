<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EnsureDemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_demo_data_only_for_an_empty_database(): void
    {
        Artisan::call('clientloop:ensure-demo');

        $this->assertDatabaseHas('users', ['email' => 'demo@clientloop.test']);
        $this->assertDatabaseHas('companies', ['slug' => 'petshop-patinhas-demo']);
    }

    public function test_it_preserves_a_database_that_already_has_a_user(): void
    {
        User::factory()->create(['email' => 'responsavel@petshop.test']);

        Artisan::call('clientloop:ensure-demo');

        $this->assertDatabaseMissing('users', ['email' => 'demo@clientloop.test']);
        $this->assertDatabaseHas('users', ['email' => 'responsavel@petshop.test']);
    }
}
