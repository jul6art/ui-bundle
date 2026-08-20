<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * A street address line — `address`, `addressLine2`. The map-pin add-on is decoration only: no
 * geocoding, no validation.
 */
final class CustomAddressType extends AbstractIconInputType
{
    #[\Override]
    public function getParent(): string
    {
        return TextType::class;
    }

    #[\Override]
    protected function iconName(): string
    {
        return 'address';
    }
}
