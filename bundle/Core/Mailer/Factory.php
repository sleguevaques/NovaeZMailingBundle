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
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Novactive\Bundle\eZMailingBundle\Core\Provider\Broadcast;
use Novactive\Bundle\eZMailingBundle\Core\Provider\MailingContent;
use Novactive\Bundle\eZMailingBundle\Core\Provider\RawMessageContent;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;

class Factory
{
    /**
     * @var ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RawMessageContent
     */
    private $messageContentProvider;

    /**
     * @var MailingContent
     */
    private $mailingContentProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Broadcast
     */
    private $broadcastProvider;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \Symfony\Component\Mailer\MailerInterface
     */
    private $mailer;

    /**
     * Factory constructor.
     */
    public function __construct(
        ConfigResolverInterface $configResolver,
        ContainerInterface $container,
        RawMessageContent $messageContentProvider,
        MailingContent $mailingContentProvider,
        LoggerInterface $logger,
        Broadcast $broadcastProvider,
        EntityManagerInterface $entityManager,
        \Symfony\Component\Mailer\MailerInterface $mailer
    ) {
        $this->configResolver = $configResolver;
        $this->container = $container;
        $this->messageContentProvider = $messageContentProvider;
        $this->mailingContentProvider = $mailingContentProvider;
        $this->logger = $logger;
        $this->broadcastProvider = $broadcastProvider;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    public function get(string $mailerDef): Mailer
    {
        if ('simple_mailer' === $mailerDef) {
            return (new SimpleMailer($this->messageContentProvider, $this->logger))->setMailer($this->mailer);
        }
        if ('newsletter_mailer' === $mailerDef) {
            $mailing = new NewsletterMailer(
                $this->container->get(SimpleMailer::class),
                $this->mailingContentProvider,
                $this->logger,
                $this->broadcastProvider,
                $this->entityManager
            );

            return $mailing->setMailer($this->mailer);
        }

        throw new RuntimeException('Mailers are not correctly defined.');
    }
}
