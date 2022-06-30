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

namespace Novactive\Bundle\eZMailingBundle\Core\Provider;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Novactive\Bundle\eZMailingBundle\Core\DataHandler\Registration;
use Novactive\Bundle\eZMailingBundle\Core\DataHandler\Unregistration;
use Novactive\Bundle\eZMailingBundle\Entity\Campaign;
use Novactive\Bundle\eZMailingBundle\Entity\ConfirmationToken;
use Novactive\Bundle\eZMailingBundle\Entity\Mailing;
use RuntimeException;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RawMessageContent
{
    /**
     * @var Environment;
     */
    private $twig;

    /**
     * @var ConfigResolver
     */
    private $configResolver;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        Environment $twig,
        ConfigResolverInterface $configResolver,
        TranslatorInterface $translator
    ) {
        $this->twig = $twig;
        $this->configResolver = $configResolver;
        $this->translator = $translator;
    }

    private function createMessage(string $subject, ?Campaign $campaign = null): Email
    {
        $prefix = $this->configResolver->getParameter('email_subject_prefix', 'nova_ezmailing');
        $message = new Email();
        $message->subject("{$prefix} {$subject}");

        if (null !== $campaign) {
            $message->from($campaign->getSenderEmail());
            $message->returnPath($campaign->getReturnPathEmail());

            return $message;
        }

        $message->from(
            $this->configResolver->getParameter('email_from_address', 'nova_ezmailing'),
            $this->configResolver->getParameter('email_from_name', 'nova_ezmailing')
        );

        $message->returnPath($this->configResolver->getParameter('email_return_path', 'nova_ezmailing'));

        return $message;
    }

    public function getStartSendingMailing(Mailing $mailing): RawMessage
    {
        $translated = $this->translator->trans('messages.start_sending.being_sent3', [], 'ezmailing');
        $message = $this->createMessage($translated, $mailing->getCampaign());
        $campaign = $mailing->getCampaign();
        $message->to($campaign->getReportEmail());
        $message->html(
            $this->twig->render('@NovaeZMailing/messages/startsending.html.twig', ['item' => $mailing]),
            'utf8',
        );

        return $message;
    }

    public function getStopSendingMailing(Mailing $mailing): RawMessage
    {
        $translated = $this->translator->trans('messages.stop_sending.sent3', [], 'ezmailing');
        $message = $this->createMessage($translated, $mailing->getCampaign());
        $campaign = $mailing->getCampaign();
        $message->to($campaign->getReportEmail());
        $message->html(
            $this->twig->render('@NovaeZMailing/messages/stopsending.html.twig', ['item' => $mailing]),
            'utf8'
        );

        return $message;
    }

    public function getRegistrationConfirmation(Registration $registration, ConfirmationToken $token): RawMessage
    {
        $translated = $this->translator->trans('messages.confirm_registration.confirm', [], 'ezmailing');
        $message = $this->createMessage($translated);
        $user = $registration->getUser();
        if (null === $user) {
            throw new RuntimeException('User cannot be empty.');
        }
        $message->to($user->getEmail());
        $message->html(
            $this->twig->render(
                '@NovaeZMailing/messages/confirmregistration.html.twig',
                [
                    'registration' => $registration,
                    'token' => $token,
                ]
            ),
            'utf8'
        );

        return $message;
    }

    public function getUnregistrationConfirmation(Unregistration $unregistration, ConfirmationToken $token): RawMessage
    {
        $translated = $this->translator->trans('messages.confirm_unregistration.confirmation', [], 'ezmailing');
        $message = $this->createMessage($translated);
        $user = $unregistration->getUser();
        if (null === $user) {
            throw new RuntimeException('User cannot be empty.');
        }
        $message->to($user->getEmail());
        $message->html(
            $this->twig->render(
                '@NovaeZMailing/messages/confirmunregistration.html.twig',
                [
                    'unregistration' => $unregistration,
                    'token' => $token,
                ]
            ),
            'utf8'
        );

        return $message;
    }
}
