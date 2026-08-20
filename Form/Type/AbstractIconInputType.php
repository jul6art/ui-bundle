<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Jul6Art\UiBundle\Ui\IconSet;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The shared half of every ready-made type in this bundle: one icon, on one side, from the
 * project's own icon set.
 *
 * Each concrete type is then three lines — the HTML input it builds on, and which icon it asks
 * for. Before extraction these were a dozen flat classes each carrying its own copy of the
 * `right_addon` / `right_type` / `right_clickable` trio and a `fa-solid` literal; the literals had
 * to go (an icon set is a project decision, cf. {@see IconSet}), and once a constructor was needed
 * to reach the icon set anyway, the duplication had no reason left to exist.
 *
 * A type with more to say than "this icon on that side" — {@see CustomMoneyType},
 * {@see CustomUnitType}, whose add-on depends on an option — extends {@see InputGroupAddOnType}
 * directly instead.
 */
abstract class AbstractIconInputType extends InputGroupAddOnType
{
    public function __construct(
        protected readonly IconSet $icons,
    ) {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $side = $this->side();

        $resolver->setDefaults([
            $side.'_addon' => $this->icons->get($this->iconName()),
            $side.'_type' => $this->addOnType(),
            $side.'_clickable' => $this->clickable(),
        ]);
    }

    /**
     * The logical icon name, resolved through {@see IconSet} — never markup.
     */
    abstract protected function iconName(): string;

    /**
     * `left` or `right`. Right is the default because a left add-on overlaps a value aligned left,
     * which is what a pre-filled input shows.
     */
    protected function side(): string
    {
        return 'right';
    }

    /**
     * `icon`, `button` or `text`. Only `button` is focusable and clickable — use it when the
     * add-on does something, like revealing a password.
     */
    protected function addOnType(): string
    {
        return 'icon';
    }

    protected function clickable(): bool
    {
        return false;
    }
}
