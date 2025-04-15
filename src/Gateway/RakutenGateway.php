<?php

namespace LaravelSmsNotificationChannel\Gateway;

use AnSms\SmsTransceiverInterface;
use AnSms\Message\Message;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class RakutenGateway implements SmsTransceiverInterface
{
    protected string $apiKey;
    protected string $endpoint;
    protected string $from;
    protected ?ClientInterface $http;
    protected ?RequestFactoryInterface $requestFactory;
    protected ?StreamFactoryInterface $streamFactory;

    public function __construct(
        ClientInterface $http = null,
        RequestFactoryInterface $requestFactory = null,
        StreamFactoryInterface $streamFactory = null
    ) {
        $this->apiKey = config('services.rakuten.api_key');
        $this->endpoint = config('services.rakuten.api_endpoint');
        $this->from = config('services.rakuten.from');

        $this->http = $http;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    public function sendMessages(array $messages): void
    {
        foreach ($messages as $message) {
            if (!$message instanceof Message) {
                throw new \InvalidArgumentException('Expected instance of ' . Message::class);
            }

            $this->sendSingleMessage($message);
        }
    }

    public function receiveMessage(): array
    {
        $request = $this->requestFactory
            ->createRequest('GET', $this->endpoint . '/receive')
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey);

        $response = $this->http->sendRequest($request);

        $body = (string)$response->getBody();
        $messages = json_decode($body, true);

        return $messages;
    }
    public function receiveDeliveryReport(): array
    {
        $request = $this->requestFactory
            ->createRequest('GET', $this->endpoint . '/delivery-report')
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey);

        $response = $this->http->sendRequest($request);

        $body = (string)$response->getBody();
        $deliveryReports = json_decode($body, true);

        return $deliveryReports;
    }

    public function checkMessageStatus(string $messageId): array
    {
        $request = $this->requestFactory
            ->createRequest('GET', $this->endpoint . '/status/' . $messageId)
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey);

        $response = $this->http->sendRequest($request);

        $body = (string)$response->getBody();
        $status = json_decode($body, true);

        return $status;
    }
}