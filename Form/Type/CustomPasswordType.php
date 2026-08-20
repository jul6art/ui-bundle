<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;

/**
 * A password field whose add-on is a real, focusable button — it reveals the value.
 *
 * The reveal itself is front-end behaviour: the shipped Twig block wires the button to a
 * `form--password` Stimulus controller, which the project must provide. Without it the button
 * renders and does nothing.
 */
final class CustomPasswordType extends AbstractIconInputType
{
    #[\Override]
    public function getParent(): string
    {
        return PasswordType::class;
    }

    #[\Override]
    protected function iconName(): string
    {
        return 'password';
    }

    #[\Override]
    protected function side(): string
    {
        return 'right';
    }

    #[\Override]
    protected function addOnType(): string
    {
        return 'button';
    }

    #[\Override]
    protected function clickable(): bool
    {
        return true;
    }
}
