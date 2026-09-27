<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideosPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_the_login_page(): void
    {
        $this->get('/admin/videos')->assertRedirect('/admin/login');
    }

    public function test_videos_page_lists_each_lesson_and_where_to_save_the_file(): void
    {
        [, $user] = $this->company();
        $this->actingAs($user);
        Storage::fake('public');

        $this->get('/admin/videos')
            ->assertSee('O que o ClientLoop faz')
            ->assertSee('Horários e serviços')
            ->assertSee('Responsável, pet e lista pronta')
            ->assertSee('Pacotes')
            ->assertSee('Marcar um horário')
            ->assertSee('Confirmar pelo WhatsApp')
            ->assertSee('Concluir, faltar, cancelar e marcar a próxima visita')
            ->assertSee('O que o painel acompanha depois')
            ->assertSee('storage/app/public/videos/01-o-que-o-clientloop-faz.mp3')
            ->assertSee('storage/app/public/videos/01-o-que-o-clientloop-faz.mp4')
            ->assertDontSee('<video', false)
            ->assertDontSee('<audio', false);
    }

    public function test_videos_page_renders_the_player_when_the_file_exists(): void
    {
        [, $user] = $this->company();
        $this->actingAs($user);
        Storage::fake('public');
        Storage::disk('public')->put('videos/01-o-que-o-clientloop-faz.mp4', 'video');

        $this->get('/admin/videos')
            ->assertSee('<video', false)
            ->assertDontSee('<audio', false)
            ->assertSee('01-o-que-o-clientloop-faz.mp4', false)
            ->assertSee('storage/app/public/videos/02-horarios-e-servicos.mp4');
    }

    public function test_videos_page_plays_the_narration_until_the_screen_recording_exists(): void
    {
        [, $user] = $this->company();
        $this->actingAs($user);
        Storage::fake('public');
        Storage::disk('public')->put('videos/01-o-que-o-clientloop-faz.mp3', 'audio');
        Storage::disk('public')->put('videos/01-o-que-o-clientloop-faz.mp4', 'video');
        Storage::disk('public')->put('videos/02-horarios-e-servicos.mp3', 'audio');

        $this->get('/admin/videos')
            ->assertSee('<video', false)
            ->assertSee('<audio', false)
            ->assertSee('02-horarios-e-servicos.mp3', false)
            ->assertDontSee('storage/app/public/videos/01-o-que-o-clientloop-faz.mp3');
    }

    /** @return array{Company, User} */
    private function company(): array
    {
        $plan = Plan::create(['name' => 'Teste', 'contact_limit' => 100, 'task_limit' => 100, 'is_default' => true]);
        $company = Company::create(['name' => 'Empresa teste '.fake()->uuid(), 'slug' => fake()->unique()->slug(), 'status' => 'active']);
        CompanySubscription::withoutGlobalScopes()->create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active']);
        $user = User::create(['company_id' => $company->id, 'name' => 'Usuária', 'email' => fake()->unique()->safeEmail(), 'password' => 'password-password']);
        $user->forceFill(['email_verified_at' => now()])->save();

        return [$company, $user];
    }
}
