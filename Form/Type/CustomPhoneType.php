<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TelType;

/**
 * A telephone number. Built on `TelType`, which gets a numeric keypad on mobile browsers.
 */
final class CustomPhoneType extends AbstractIconInputType
{
    #[\Override]
    public function getParent(): string
    {
        return TelType::class;
    }

    #[\Override]
    protected function iconName(): string
    {
        return 'phone';
    }
}
