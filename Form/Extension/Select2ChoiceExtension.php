<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Every `ChoiceType` rendered as a list — so `EntityType`, `EnumType`, `CountryType`… too — gets the
 * application's Select2 controller (`ui.select2_identifier`, `ui--select2` by default).
 *
 * The rule of this ecosystem is that no native `<select>` reaches the screen, and a rule held field
 * by field is a rule that leaks: each application wrote the attribute on most of its choices, and
 * one forgot it on all of its forms. Here it is structural.
 *
 * - An expanded choice (checkboxes, radios) is left alone: it is not a `<select>`.
 * - A controller the field already declares is kept; Select2 is appended, never duplicated.
 * - The « clear » button is offered on an optional field only: clearing a required one could only
 *   end in a validation error. A field that sets `allow-clear` itself keeps its value.
 */
final class Select2ChoiceExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly string $identifier,
    ) {
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (true === $options['expanded']) {
            return;
        }

        /** @var array<string, mixed> $attr */
        $attr = $view->vars['attr'] ?? [];
        $controllers = array_values(array_filter(explode(' ', \is_string($attr['data-controller'] ?? null) ? $attr['data-controller'] : '')));
        if (!\in_array($this->identifier, $controllers, true)) {
            $controllers[] = $this->identifier;
        }
        $attr['data-controller'] = implode(' ', $controllers);
        $attr['data-'.$this->identifier.'-allow-clear-value'] ??= true === $options['required'] ? 'false' : 'true';

        $view->vars['attr'] = $attr;
    }
}
