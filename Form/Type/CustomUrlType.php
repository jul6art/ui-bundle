<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A URL. Built on `UrlType`, which prepends a scheme when the user omits one.
 *
 * ⚠️ `default_protocol` is stated rather than inherited. Symfony 7.1 deprecates leaving it unset, and
 * 8.0 turns the default to `null` — no scheme prepended any more. Stated here, `example.com` keeps
 * becoming `http://example.com` across the upgrade; a project that wants another scheme, or none,
 * passes the option.
 */
final class CustomUrlType extends AbstractIconInputType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('default_protocol', 'http');
    }

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
