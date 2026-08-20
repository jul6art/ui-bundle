<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\FormView;

/**
 * Attaches the `form--decimal` Stimulus controller to a numeric input without clobbering whatever
 * the caller already put on it.
 *
 * The append rather than assign is the whole point: `data-controller` is a space-separated list,
 * and a form declaring `attr: {'data-controller': 'my--thing'}` would otherwise lose its own
 * controller the moment the field became a money field. That failure is silent — the input renders,
 * looks right, and one behaviour is simply gone.
 */
trait DecimalControllerTrait
{
    /**
     * @param array<string, mixed> $options
     */
    private function attachDecimalController(FormView $view, array $options): void
    {
        $scale = is_numeric($options['scale'] ?? null) ? (int) $options['scale'] : 2;

        $attr = \is_array($view->vars['attr'] ?? null) ? $view->vars['attr'] : [];

        $existing = \is_string($attr['data-controller'] ?? null) ? $attr['data-controller'] : '';
        if (!str_contains($existing, 'form--decimal')) {
            $attr['data-controller'] = trim($existing.' form--decimal');
        }

        $attr['data-form--decimal-decimals-value'] = (string) $scale;

        // Pas de `inputmode` ici : `NumberType::buildView()` le pose lui-même, après ce code, et
        // le pose mieux — `numeric` quand `scale` vaut 0, `decimal` sinon. Une ligne de plus ici
        // serait écrasée sans rien signaler, et sa seule fonction serait de laisser croire qu'un
        // projet peut le choisir. Il ne peut pas, et c'est amont.

        $view->vars['attr'] = $attr;
    }
}
