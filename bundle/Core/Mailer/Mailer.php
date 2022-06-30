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

abstract class Mailer
{
    /**
     * @var \Symfony\Component\Mailer\Mailer
     */
    protected $mailer;

    public function setMailer(\Symfony\Component\Mailer\Mailer $mailer): self
    {
        $this->mailer = $mailer;

        return $this;
    }
}
