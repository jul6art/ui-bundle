<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * An intra-community VAT number.
 */
final class CustomVatNumberType extends AbstractIconInputType
{
    #[\Override]
    public function getParent(): string
    {
        return TextType::class;
    }

    #[\Override]
    protected function iconName(): string
    {
        return 'vat_number';
    }
}
