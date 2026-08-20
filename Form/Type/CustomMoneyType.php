<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Jul6Art\UiBundle\Ui\IconSet;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A monetary amount, with the currency shown inside the input.
 *
 * ```php
 * $builder->add('total', CustomMoneyType::class, ['currency' => 'CHF', 'scale' => 2]);
 * ```
 *
 * Built on `NumberType` rather than Symfony's `MoneyType` for one reason: `MoneyType` divides by
 * 100 and stores integer cents. This one keeps the scalar the entity holds, which is what a
 * `decimal` column maps to, and leaves `scale` / `step` / `min` / `max` usable.
 *
 * The add-on sits on the **right** — the French convention («1 000,00 €») and the side that does
 * not overlap a value aligned left.
 *
 * ## The currency symbol, and when there is none
 *
 * A configured symbol is shown as an icon; a currency with no configured symbol shows its ISO code
 * as text. That fallback is the correct answer for a good third of the world's currencies (CHF,
 * PLN, SEK…) and is deliberately not papered over: a euro sign beside a Swiss-franc amount is a
 * reporting error, not a cosmetic one. Cf. {@see IconSet::currency()}.
 *
 * ## What formats the input
 *
 * The type attaches a `form--decimal` Stimulus controller and an `inputmode`, and passes `scale`
 * down as `data-form--decimal-decimals-value`. **The controller itself is not shipped by this
 * bundle** — a bundle exposing Stimulus controllers has to pick an asset strategy (AssetMapper or
 * Encore) and impose it on every consumer. Without a controller of that name the input still
 * works: it is a plain number field, unformatted, and non-numeric keystrokes are no longer
 * filtered. The add-on is decoration; nothing here validates an amount.
 */
final class CustomMoneyType extends InputGroupAddOnType
{
    use DecimalControllerTrait;

    public function __construct(
        private readonly IconSet $icons,
    ) {
    }

    #[\Override]
    public function getParent(): string
    {
        return NumberType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['currency' => 'EUR']);
        $resolver->setAllowedTypes('currency', 'string');
    }

    /**
     * @param array<string, mixed> $options
     */
    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $currency = strtoupper(\is_string($options['currency'] ?? null) ? $options['currency'] : 'EUR');
        $symbol = $this->icons->currency($currency);

        if (null !== $symbol) {
            $view->vars['right_addon'] = $symbol;
            $view->vars['right_type'] = 'icon';
        } else {
            // Le code ISO est du texte utilisateur dans un gabarit rendu en `raw` : il est échappé
            // ici, à la source, et non dans le gabarit — c'est le même add-on qui sert aux icônes,
            // qui sont du balisage par nature.
            $view->vars['right_addon'] = htmlspecialchars($currency, \ENT_QUOTES);
            $view->vars['right_type'] = 'text';
        }

        $view->vars['right_clickable'] = false;

        $this->attachDecimalController($view, $options);
    }
}
