<?php

namespace LaravelSmsNotificationChannel\Gateway;


use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use LaravelSmsNotificationChannel\Gateway\GatewayInterface;
use AnSms\Message\DeliveryReport\DeliveryReport;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Exception\SendException;
use Illuminate\Support\Facades\Http;

class RakutenGateway implements GatewayInterface
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $authUrl = 'https://api.cpaas.symphony.rakuten.net/auth/v1/token';
    protected string $smsUrl = 'https://api.cpaas.symphony.rakuten.net/sms/v1/submit';

    public function __construct(string $apiKey, string $apiSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        $token = $this->getAccessToken();

        $payload = [
            'from' => $message->getFrom(),
            'to' => $message->getTo(),
            'message_type' => 'text',
            'text_message' => [
                'text' => $message->getText(),
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->smsUrl, $payload);

        if ($response->failed()) {
            throw new SendException("Rakuten SMS sending failed: " . $response->body());
        }
    }

    /**
     * @param MessageInterface[] $messages
     * @throws SendException
     */
    public function sendMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->sendMessage($message);
        }
    }

    /**
     * @throws ReceiveException
     */
    public function receiveMessage(array $data): MessageInterface
    {
        try {
            $message = Message::create(
                $data['to'] ?? '817000000000',
                $data['text'] ?? '-',
                $data['from'] ?? '817001111111'
            );

            $message->setId($data['message_id'] ?? uniqid());

            return $message;
        } catch (\Throwable $e) {
            throw new ReceiveException('Failed to receive message.', 0, $e);
        }
    }

    /**
     * @throws ReceiveException
     */
    public function receiveDeliveryReport(array $data): DeliveryReportInterface
    {
        return new DeliveryReport(
            $data['message_id'] ?? '',
            $data['status'] ?? ''
        );
    }

    protected function getAccessToken(): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$this->apiKey}:{$this->apiSecret}"),
            'Accept' => 'application/json',
        ])->post($this->authUrl, [
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            throw new SendException('Rakuten authentication failed: ' . $response->body());
        }

        return $response->json()['jwt_token'] ?? throw new SendException('Token not found in authentication response.');
    }
}