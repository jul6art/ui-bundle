<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * A vehicle registration plate.
 *
 * Normalising the value (upper-casing, trimming) is the entity's job, not the form type's: a
 * plate arriving through an import or an API must be normalised too, and a form type would only
 * cover the one path a human takes.
 */
final class CustomLicensePlateType extends AbstractIconInputType
{
    #[\Override]
    public function getParent(): string
    {
        return TextType::class;
    }

    #[\Override]
    protected function iconName(): string
    {
        return 'license_plate';
    }
}
