<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * A French SIRET establishment number. The add-on is a hint, not a checksum: validating a SIRET is
 * a constraint on the entity, where every write path goes through it.
 */
final class CustomSiretType extends AbstractIconInputType
{
    #[\Override]
    public function getParent(): string
    {
        return TextType::class;
    }

    #[\Override]
    protected function iconName(): string
    {
        return 'siret';
    }
}
