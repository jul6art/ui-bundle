<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\SearchType;

/**
 * A search box, with the magnifier on the **left** — the one place a left add-on is right, because
 * a search field is empty when it matters and the icon reads as a label.
 */
final class CustomSearchType extends AbstractIconInputType
{
    #[\Override]
    public function getParent(): string
    {
        return SearchType::class;
    }

    #[\Override]
    protected function iconName(): string
    {
        return 'search';
    }

    #[\Override]
    protected function side(): string
    {
        return 'left';
    }
}
