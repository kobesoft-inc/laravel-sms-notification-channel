<?php

namespace LaravelSmsNotificationChannel\Gateway;

use AnSms\SmsTransceiverInterface;
use AnSms\Message\Address\AddressInterface;
use AnSms\Message\MessageInterface;
use AnSms\Message\Message;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

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

    public function sendMessage(MessageInterface $message): void
    {
        try {
            $this->sendSingleMessage($message);
        } catch (\Throwable $e) {
            throw new SendException('Failed to send message', 0, $e);
        }
    }

    public function sendMessages(array $messages): void
    {
        foreach ($messages as $message) {
            if (!$message instanceof MessageInterface) {
                throw new \InvalidArgumentException('Each message must implement MessageInterface');
            }

            $this->sendMessage($message);
        }
    }

    public function receiveMessage(array $data): MessageInterface
    {
        $message = new Message(
            $data['recipient'],
            $data['body']
        );

        return $message;
    }

    public function receiveDeliveryReport(array $data): MessageInterface
    {
        $deliveryReport = new Message(
            $data['recipient'],
            $data['status']
        );

        return $deliveryReport;
    }

    public function checkMessageStatus(array $data): MessageInterface
    {
        $status = new Message(
            $data['recipient'],
            $data['status']
        );

        return $status;
    }


    public function setDefaultFrom(AddressInterface|string|null $defaultFrom): void
    {
        if ($defaultFrom === null) {
            $this->from = null;
        } elseif ($defaultFrom instanceof AddressInterface) {
            $this->from = $defaultFrom->getAddress();
        } else {
            $this->from = $defaultFrom;
        }
    }
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}