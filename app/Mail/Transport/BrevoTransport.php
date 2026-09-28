<?php

declare(strict_types=1);

namespace App\Mail\Transport;

use Illuminate\Http\Client\Factory as HttpFactory;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

final class BrevoTransport extends AbstractTransport
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $apiKey,
        private readonly int $timeout = 30,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        try {
            $response = $this->http
                ->acceptJson()
                ->withHeaders(['api-key' => $this->apiKey])
                ->timeout($this->timeout)
                ->post(self::ENDPOINT, $this->payload($email, $envelope));
        } catch (\Throwable $exception) {
            throw new TransportException(
                sprintf('Request to Brevo API failed. Reason: %s.', $exception->getMessage()),
                is_int($exception->getCode()) ? $exception->getCode() : 0,
                $exception,
            );
        }

        if (! $response->successful()) {
            $reason = $response->json('message') ?: $response->body();

            throw new TransportException(sprintf(
                'Request to Brevo API failed with status %d. Reason: %s.',
                $response->status(),
                $reason ?: 'Unknown error',
            ));
        }

        if (is_string($messageId = $response->json('messageId'))) {
            $message->setMessageId($messageId);
        }
    }

    /** @return array<string, mixed> */
    private function payload(Email $email, Envelope $envelope): array
    {
        $payload = [
            'sender' => $this->address($envelope->getSender()),
            'to' => $this->addresses($this->recipients($email, $envelope)),
            'cc' => $this->addresses($email->getCc()),
            'bcc' => $this->addresses($email->getBcc()),
            'replyTo' => $email->getReplyTo() === [] ? null : $this->address($email->getReplyTo()[0]),
            'subject' => $email->getSubject(),
            'htmlContent' => $email->getHtmlBody(),
            'textContent' => $email->getTextBody(),
            'headers' => $this->headers($email),
            'attachment' => $this->attachments($email),
        ];

        return array_filter($payload, static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /** @return Address[] */
    private function recipients(Email $email, Envelope $envelope): array
    {
        return array_values(array_filter(
            $envelope->getRecipients(),
            static fn (Address $address): bool => ! in_array($address, [...$email->getCc(), ...$email->getBcc()], true),
        ));
    }

    /** @return array{email: string, name?: string} */
    private function address(Address $address): array
    {
        return array_filter([
            'email' => $address->getAddress(),
            'name' => $address->getName(),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /** @param Address[] $addresses
     * @return array<int, array{email: string, name?: string}>
     */
    private function addresses(array $addresses): array
    {
        return array_map($this->address(...), $addresses);
    }

    /** @return array<string, string> */
    private function headers(Email $email): array
    {
        $headers = [];
        $standardHeaders = ['from', 'to', 'cc', 'bcc', 'reply-to', 'sender', 'subject', 'content-type', 'date', 'message-id'];

        foreach ($email->getHeaders()->all() as $header) {
            if (in_array(strtolower($header->getName()), $standardHeaders, true)) {
                continue;
            }

            $headers[$header->getName()] = $header->getBodyAsString();
        }

        return $headers;
    }

    /** @return array<int, array{name: string, content: string}> */
    private function attachments(Email $email): array
    {
        return array_map(static fn ($attachment): array => [
            'name' => $attachment->getFilename() ?? 'attachment',
            'content' => base64_encode($attachment->getBody()),
        ], $email->getAttachments());
    }

    public function __toString(): string
    {
        return 'brevo+api';
    }
}
