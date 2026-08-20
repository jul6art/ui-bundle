<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A text input with an add-on pinned inside it, left or right — an icon, a button or a short label.
 *
 * This is the base every `Custom*Type` in this bundle extends, and it is usable directly when none
 * of them fits:
 *
 * ```php
 * $builder->add('reference', InputGroupAddOnType::class, [
 *     'right_addon' => '<i class="fa-solid fa-hashtag"></i>',
 *     'right_type' => 'icon',       // icon | button | text
 * ]);
 * ```
 *
 * ## What actually renders it
 *
 * Nothing here draws anything. The six `view.vars` set below are read by the Twig block
 * `input_group_addon_widget`, shipped in `Resources/views/form/input_group_addon.html.twig`, and a
 * project that does not register that theme gets a plain input with the add-on silently dropped:
 *
 * ```yaml
 * # config/packages/twig.yaml
 * twig:
 *     form_themes:
 *         - '@Ui/form/input_group_addon.html.twig'
 * ```
 *
 * Every subclass returns `input_group_addon` from `getBlockPrefix()`, which is what routes it to
 * that block. Override the block in the project's own theme — registered *after* the bundle's — to
 * change the markup without touching the types.
 *
 * @extends AbstractType<mixed>
 */
class InputGroupAddOnType extends AbstractType
{
    #[\Override]
    public function getParent(): string
    {
        return TextType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'left_addon' => null,
            'right_addon' => null,

            // icon | button | text — `button` is the only one that is focusable and clickable.
            'left_type' => 'icon',
            'right_type' => 'icon',

            'left_clickable' => false,
            'right_clickable' => false,

            'type' => TextType::class,
        ]);
    }

    /**
     * Routes the type — and every subclass, which is the point — to the bundle's Twig block.
     *
     * Without it a type falls back to the block Symfony derives from its class name
     * (`input_group_add_on_widget`), which no theme declares: the field renders as a plain input
     * and the add-on vanishes with nothing logged. Declaring it here rather than in each subclass
     * means one block renders the whole family, and a project overriding that block restyles all
     * of them at once.
     */
    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'input_group_addon';
    }

    /**
     * @param array<string, mixed> $options
     */
    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['left_addon'] = $options['left_addon'] ?? null;
        $view->vars['right_addon'] = $options['right_addon'] ?? null;

        $view->vars['left_type'] = $options['left_type'] ?? null;
        $view->vars['right_type'] = $options['right_type'] ?? null;

        $view->vars['left_clickable'] = $options['left_clickable'] ?? null;
        $view->vars['right_clickable'] = $options['right_clickable'] ?? null;
    }
}
