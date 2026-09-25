<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportTemplateDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_download_only_the_responsible_and_pet_template(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $customersResponse = $this->actingAs($user)
            ->get(route('imports.template', ['type' => 'customers']))
            ->assertOk()
            ->assertDownload('modelo-importacao-responsaveis.csv');
        $this->assertStringContainsString('responsible_name,responsible_phone,pet_name,last_activity_at,next_return_at,opted_out', $customersResponse->streamedContent());

        $this->actingAs($user)
            ->get(route('imports.template', ['type' => 'appointments']))
            ->assertNotFound();
    }
}
