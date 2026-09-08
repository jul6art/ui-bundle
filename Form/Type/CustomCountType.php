<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A WHOLE number with a unit shown inside the input — hours, months, days, people.
 *
 * ```php
 * $builder->add('responseTimeHours', CustomCountType::class, ['unit' => 'h']);
 * ```
 *
 * ## Why this exists next to {@see CustomUnitType}
 *
 * `CustomUnitType` is parented on `NumberType` and submits a **float**. Attaching it to a property
 * typed `?int` — an SLA in hours, a service interval in months — hands a float to an int setter and
 * raises a `TypeError` in strict mode: a 500 on a perfectly ordinary entry. The workaround people
 * reach for, `InputGroupAddOnType` directly, is parented on `TextType` and hands over a **string**,
 * which fails the same way for the same reason.
 *
 * So a project wanting "12 h" in the box had three options, and all three were wrong. That is what
 * this type is for: `IntegerType` underneath, the same add-on on top.
 *
 * ⚠️ **No decimal controller here**, unlike its two decimal siblings. The controller formats a
 * scale, and a whole number has none; attaching it would put a `data-form--decimal-decimals-value`
 * of `0` on a field that cannot show decimals anyway, and `IntegerType` already sets
 * `inputmode="numeric"` for the keypad.
 *
 * ⚠️ An empty `unit` renders **no add-on at all**, rather than an empty box — the same rule as
 * `CustomUnitType`, so a unit only known at runtime degrades to a plain number input instead of
 * showing a stray decoration.
 */
final class CustomCountType extends InputGroupAddOnType
{
    #[\Override]
    public function getParent(): string
    {
        return IntegerType::class;
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
            // A unit comes from business data, not from an icon set: it is escaped.
            $view->vars['right_addon'] = htmlspecialchars($unit, \ENT_QUOTES);
            $view->vars['right_type'] = 'text';
            $view->vars['right_clickable'] = false;
        }
    }
}
