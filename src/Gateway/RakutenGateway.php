<?php

namespace LaravelSmsNotificationChannel\Gateway;


use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Message\DeliveryReport\DeliveryReport;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\Message;
use AnSms\Message\MessageInterface;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class RakutenGateway implements GatewayInterface
{
    protected string $apiKey;
    protected string $endpoint;
    protected string $from;

    public function __construct(
        string $apiKey,
        string $endpoint,
        string $from
    ) {
        if (empty($apiKey) || empty($endpoint) || empty($from)) {
            throw new InvalidArgumentException('API Key, Endpoint, and From are required');
        }

        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
        $this->from = $from;
    }

    /**
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message): void
    {
        try {
            // 라쿠텐 SMS API로 HTTP POST 요청
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post($this->endpoint, [
                'from' => $this->from,
                'to' => $message->getTo(),
                'message' => $message->getText(),
            ]);

            $responseData = $response->json();

            // 라쿠텐 API의 성공적인 응답을 확인
            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                $message->setId($responseData['message_id']);
            } else {
                throw new SendException('Failed to send SMS: ' . $responseData['error_message'] ?? 'Unknown error');
            }

        } catch (\Exception $e) {
            throw new SendException('HTTP request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param Message[] $messages
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
        if (empty($data['to']) || empty($data['body']) || empty($data['from']) || empty($data['message_id'])) {
            throw new ReceiveException(sprintf(
                'Invalid receive message data. Data received: %s',
                var_export($data, true)
            ));
        }

        $receivedMessage = Message::create(
            $data['to'],
            trim($data['body']),
            $data['from']
        );

        $receivedMessage->setId($data['message_id']);

        return $receivedMessage;
    }

    /**
     * @throws ReceiveException
     */
    public function receiveDeliveryReport(array $data): DeliveryReportInterface
    {
        if (empty($data['message_id']) || empty($data['status'])) {
            throw new ReceiveException(sprintf(
                'Invalid message delivery report data. Data received: %s',
                var_export($data, true)
            ));
        }

        return new DeliveryReport($data['message_id'], $data['status']);
    }
}