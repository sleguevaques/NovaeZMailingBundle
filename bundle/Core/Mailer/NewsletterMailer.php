<?php

/**
 * NovaeZMailingBundle Bundle.
 *
 * @package   Novactive\Bundle\eZMailingBundle
 *
 * @author    Novactive <s.morel@novactive.com>
 * @copyright 2018 Novactive
 * @license   https://github.com/Novactive/NovaeZMailingBundle/blob/master/LICENSE MIT Licence
 */

declare(strict_types=1);

namespace Novactive\Bundle\eZMailingBundle\Core\Mailer;

use Doctrine\ORM\EntityManagerInterface;
use Novactive\Bundle\eZMailingBundle\Core\Provider\Broadcast;
use Novactive\Bundle\eZMailingBundle\Core\Provider\RawMessageContent;
use Novactive\Bundle\eZMailingBundle\Entity\Mailing as MailingEntity;
use Novactive\Bundle\eZMailingBundle\Entity\User;
use Novactive\Bundle\eZMailingBundle\Core\Provider\MailingContent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * Class Mailing.
 */
class NewsletterMailer extends Mailer
{
    /**
     * @var SimpleMailer
     */
    private $simpleMailer;

    /**
     * @var RawMessageContent
     */
    private $contentProvider;

    /**
     * @var Broadcast
     */
    private $broadcastProvider;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Mailing constructor.
     */
    public function __construct(
        SimpleMailer $simpleMailer,
        MailingContent $contentProvider,
        LoggerInterface $logger,
        Broadcast $broadcastProvider,
        EntityManagerInterface $entityManager
    ) {
        $this->simpleMailer = $simpleMailer;
        $this->contentProvider = $contentProvider;
        $this->logger = $logger;
        $this->broadcastProvider = $broadcastProvider;
        $this->entityManager = $entityManager;
    }

    public function sendMailing(MailingEntity $mailing, string $forceRecipient = null): void
    {
        $nativeHtml = $this->contentProvider->preFetchContent($mailing);
        $broadcast = $this->broadcastProvider->start($mailing, $nativeHtml);

        $this->simpleMailer->sendStartSendingMailingMessage($mailing);

        if ($forceRecipient) {
            $fakeUser = new User();
            $fakeUser->setEmail($forceRecipient);
            $fakeUser->setFirstName('XXXX');
            $fakeUser->setLastName('YYYY');
            $contentMessage = $this->contentProvider->getContentMailing($mailing, $fakeUser, $broadcast);
            $this->logger->debug("Mailing Mailer starts to test {$contentMessage->getSubject()}.");
            $this->sendMessage($contentMessage);
        } else {
            $campaign = $mailing->getCampaign();
            $this->logger->notice("Mailing Mailer starts to send Mailing {$mailing->getName()}");
            $recipientCounts = 0;
            $userRepo = $this->entityManager->getRepository(User::class);
            $recipients = $userRepo->findValidRecipients($campaign->getMailingLists());
            foreach ($recipients as $user) {
                /** @var User $user */
                $contentMessage = $this->contentProvider->getContentMailing($mailing, $user, $broadcast);

                // Try to send 3 times by recipients then log email error and keep going
                for ($sendTry = 1; $sendTry <= 3; $sendTry++) {
                    try {
                        $this->sendMessage($contentMessage);
                        $sendTry = 10;
                        ++$recipientCounts;
                        break;
                    } catch ( \Exception $exception) {
                        $this->logger->notice("Mailer failed for email : {$user->getEmail()}. Attempt sending : $sendTry.");

                        if ( $sendTry == 3) {
                            $this->logger->error("This recipient ({$user->getEmail()}) will not receive the newsletter !!!");
                            $this->logger->error($exception->getMessage());
                        }
                    }
                }

                if (0 === $recipientCounts % 10) {
                    $broadcast->setEmailSentCount($recipientCounts);
                    $this->broadcastProvider->store($broadcast);
                }
            }
            $this->broadcastProvider->store($broadcast);
            $this->logger->notice("Mailing {$mailing->getName()} induced {$recipientCounts} emails sent.");
        }

        $this->simpleMailer->sendStopSendingMailingMessage($mailing);
        $this->broadcastProvider->end($broadcast);
    }

    private function sendMessage(RawMessage $message)
    {
        $message->getHeaders()->addTextHeader('X-Transport', 'newsletter_mailer');

        return $this->mailer->send($message);
    }
}
