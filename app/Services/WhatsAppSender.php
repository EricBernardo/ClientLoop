<?php

namespace App\Services;

use App\Models\ContactAttempt;
use App\Models\ContactTask;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppSender
{
    /**
     * Tries Meta Cloud API when configured; otherwise returns the manual wa.me URL.
     *
     * @return array{mode: string, url: ?string, sent: bool, error: ?string}
     */
    public function send(ContactTask $task): array
    {
        $manualUrl = $task->whatsappUrl();
        $token = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        if (blank($token) || blank($phoneNumberId) || blank($task->customer?->phone)) {
            return ['mode' => 'manual', 'url' => $manualUrl, 'sent' => false, 'error' => null];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => preg_replace('/\D+/', '', (string) $task->customer->phone),
                    'type' => 'text',
                    'text' => ['body' => $task->rendered_message ?: 'Olá!'],
                ]);

            if ($response->successful()) {
                ContactAttempt::query()->create([
                    'contact_task_id' => $task->id,
                    'user_id' => auth()->id(),
                    'outcome' => 'api_sent',
                    'note' => 'Enviado via WhatsApp Cloud API',
                    'attempted_at' => now(),
                ]);

                return ['mode' => 'api', 'url' => $manualUrl, 'sent' => true, 'error' => null];
            }

            return ['mode' => 'api', 'url' => $manualUrl, 'sent' => false, 'error' => $response->json('error.message') ?? 'Falha no envio automático'];
        } catch (\Throwable $exception) {
            Log::warning('whatsapp.api_failed', ['message' => $exception->getMessage(), 'task_id' => $task->id]);

            return ['mode' => 'api', 'url' => $manualUrl, 'sent' => false, 'error' => $exception->getMessage()];
        }
    }
}
