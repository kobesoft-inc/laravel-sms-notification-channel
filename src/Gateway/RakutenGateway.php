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

    /**
     * send message
     *
     * @param Message $message
     * @return void
     */
    public function sendMessage(Message $message): void
    {
        if (!$this->http || !$this->requestFactory || !$this->streamFactory) {
            throw new \RuntimeException('HTTP client, request factory, and stream factory are required.');
        }

        $body = json_encode([
            'from'    => $this->from,
            'to'      => $message->getRecipient(),
            'message' => $message->getBody(),
        ]);

        $request = $this->requestFactory
            ->createRequest('POST', $this->endpoint)
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($body));

        $this->http->sendRequest($request);
    }
}