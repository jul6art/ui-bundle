<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\EmailType;

/**
 * An e-mail address. Built on `EmailType`, so the browser validates the shape before submission.
 */
final class CustomEmailType extends AbstractIconInputType
{
    #[\Override]
    public function getParent(): string
    {
        return EmailType::class;
    }

    #[\Override]
    protected function iconName(): string
    {
        return 'email';
    }
}
