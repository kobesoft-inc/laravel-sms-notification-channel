<?php declare(strict_types=1);

/**
 * An SMS library.
 *
 * @copyright Copyright (c) 2017 Andreas Nilsson
 * @license   MIT
 */

 namespace LaravelSmsNotificationChannel\Gateway;

use AnSms\Exception\ReceiveException;
use AnSms\Exception\SendException;
use AnSms\Message\Address\AddressInterface;
use AnSms\Message\DeliveryReport\DeliveryReportInterface;
use AnSms\Message\MessageInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Interface for sending and receiving SMS text messages.
 */
interface SmsTransceiverInterface extends LoggerAwareInterface
{
    /**
     * @throws SendException
     */
    public function sendMessage(MessageInterface $message) : void;

    /**
     * @param MessageInterface[] $messages
     * @throws SendException
     */
    public function sendMessages(array $messages): void;

    /**
     * @throws ReceiveException
     */
    public function receiveMessage(array $data) : MessageInterface;

    /**
     * @throws ReceiveException
     */
    public function receiveDeliveryReport(array $data) : DeliveryReportInterface;

    public function setDefaultFrom(AddressInterface|string|null $defaultFrom): void;
}