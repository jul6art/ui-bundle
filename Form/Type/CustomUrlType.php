<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\UrlType;

/**
 * A URL. Built on `UrlType`, which prepends a scheme when the user omits one.
 */
final class CustomUrlType extends AbstractIconInputType
{
    #[\Override]
    public function getParent(): string
    {
        return UrlType::class;
    }

    #[\Override]
    protected function iconName(): string
    {
        return 'url';
    }
}
