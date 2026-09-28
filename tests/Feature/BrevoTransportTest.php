<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class BrevoTransportTest extends TestCase
{
    public function test_transactional_mail_is_sent_through_the_brevo_api(): void
    {
        config()->set('mail.mailers.brevo', [
            'transport' => 'brevo',
            'key' => 'brevo-test-key',
            'timeout' => 30,
        ]);
        config()->set('mail.from', ['address' => 'hello@clientloop.test', 'name' => 'ClientLoop']);
        Mail::purge('brevo');

        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<brevo-message-id>'], 201),
        ]);

        Mail::mailer('brevo')->raw('Teste de e-mail do ClientLoop.', function (Message $message): void {
            $message->to('tutor@example.com', 'Tutor')->subject('Teste ClientLoop');
        });

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->method() === 'POST'
                && $request->hasHeader('api-key', 'brevo-test-key')
                && data_get($request->data(), 'sender.email') === 'hello@clientloop.test'
                && data_get($request->data(), 'to.0.email') === 'tutor@example.com'
                && data_get($request->data(), 'subject') === 'Teste ClientLoop'
                && data_get($request->data(), 'textContent') === 'Teste de e-mail do ClientLoop.';
        });
    }

    public function test_brevo_api_error_rejects_the_message(): void
    {
        config()->set('mail.mailers.brevo', [
            'transport' => 'brevo',
            'key' => 'brevo-test-key',
            'timeout' => 30,
        ]);
        config()->set('mail.from', ['address' => 'hello@clientloop.test', 'name' => 'ClientLoop']);
        Mail::purge('brevo');

        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response(['message' => 'unauthorized'], 401),
        ]);

        $this->expectException(TransportException::class);

        Mail::mailer('brevo')->raw('Teste de e-mail do ClientLoop.', function (Message $message): void {
            $message->to('tutor@example.com')->subject('Teste ClientLoop');
        });
    }
}
