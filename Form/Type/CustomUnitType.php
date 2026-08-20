<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A decimal quantity with a unit shown inside the input — hours, percent, kilograms.
 *
 * ```php
 * $builder->add('duration', CustomUnitType::class, ['unit' => 'h', 'scale' => 2]);
 * ```
 *
 * For amounts of money use {@see CustomMoneyType} instead: it resolves a currency symbol and falls
 * back to an ISO code, which a free-text unit cannot do.
 *
 * An empty `unit` renders **no add-on at all**, rather than an empty box — a field whose unit is
 * only known at runtime (a product's own unit, say) then degrades to a plain number input instead
 * of showing a stray decoration.
 *
 * Like {@see CustomMoneyType}, this attaches a `form--decimal` Stimulus controller that the project
 * must provide; see that class for what happens when it is absent.
 */
final class CustomUnitType extends InputGroupAddOnType
{
    use DecimalControllerTrait;

    #[\Override]
    public function getParent(): string
    {
        return NumberType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['unit' => '']);
        $resolver->setAllowedTypes('unit', 'string');
    }

    /**
     * @param array<string, mixed> $options
     */
    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $unit = \is_string($options['unit'] ?? null) ? $options['unit'] : '';

        if ('' !== $unit) {
            // Une unité vient d'une donnée métier, pas d'un jeu d'icônes : elle est échappée.
            $view->vars['right_addon'] = htmlspecialchars($unit, \ENT_QUOTES);
            $view->vars['right_type'] = 'text';
            $view->vars['right_clickable'] = false;
        }

        $this->attachDecimalController($view, $options);
    }
}
