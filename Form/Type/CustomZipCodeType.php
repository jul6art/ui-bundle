<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * A postal code.
 */
final class CustomZipCodeType extends AbstractIconInputType
{
    #[\Override]
    public function getParent(): string
    {
        return TextType::class;
    }

    #[\Override]
    protected function iconName(): string
    {
        return 'zip_code';
    }
}
