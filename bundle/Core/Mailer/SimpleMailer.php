<?php

declare(strict_types=1);

namespace Novactive\Bundle\eZMailingBundle\Core\Mailer;

use Novactive\Bundle\eZMailingBundle\Core\DataHandler\Registration;
use Novactive\Bundle\eZMailingBundle\Core\DataHandler\Unregistration;
use Novactive\Bundle\eZMailingBundle\Core\Provider\MessageContentInforca;
use Novactive\Bundle\eZMailingBundle\Core\Provider\MailingContent;
use Novactive\Bundle\eZMailingBundle\Entity\ConfirmationToken;
use Novactive\Bundle\eZMailingBundle\Entity\Mailing as MailingEntity;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class SimpleMailerInforca extends Mailer
{
    /**
     * @var MessageContentInforca
     */
    private $messageProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(MessageContentInforca $messageProvider, LoggerInterface $logger)
    {
        $this->messageProvider = $messageProvider;
        $this->logger = $logger;
    }

    public function sendStartSendingMailingMessage(MailingEntity $mailing): void
    {
        $message = $this->messageProvider->getStartSendingMailing($mailing);
        $this->sendMessage($message);
    }

    public function sendStopSendingMailingMessage(MailingEntity $mailing): void
    {
        $message = $this->messageProvider->getStopSendingMailing($mailing);
        $this->sendMessage($message);
    }

    public function sendRegistrationConfirmation(Registration $registration, ConfirmationToken $token): void
    {
        $message = $this->messageProvider->getRegistrationConfirmation($registration, $token);
        $this->sendMessage($message);
    }

    public function sendUnregistrationConfirmation(Unregistration $unregistration, ConfirmationToken $token): void
    {
        $message = $this->messageProvider->getUnregistrationConfirmation($unregistration, $token);
        $this->sendMessage($message);
    }

    private function sendMessage(RawMessage $message)
    {
        $this->logger->debug("Simple Mailer sends {$message->getSubject()}.");
        $message->getHeaders()->addTextHeader('X-Transport', 'simple_mailer');

//        dump($message);
//        dump($this->mailer); exit;

        return $this->mailer->send($message);
    }
}
