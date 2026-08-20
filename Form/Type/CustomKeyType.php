<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * A technical key or identifier the user types — an API key, a machine name, a slug.
 */
final class CustomKeyType extends AbstractIconInputType
{
    #[\Override]
    public function getParent(): string
    {
        return TextType::class;
    }

    #[\Override]
    protected function iconName(): string
    {
        return 'key';
    }
}
