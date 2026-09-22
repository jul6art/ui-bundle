<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Jul6Art\UiBundle\Form\ChoiceList\BoundedLazyChoiceLoader;
use Symfony\Bridge\Doctrine\Form\ChoiceList\IdReader;
use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * An entity field searched through the API — the page carries its current value and nothing else.
 *
 * ```php
 * $builder->add('technician', AutocompleteEntityType::class, [
 *     'class' => User::class,
 *     'choice_label' => 'fullName',
 *     'autocomplete_url' => '/api/users?roles=ROLE_TECHNICIAN&isActive=true',
 *     'text_key' => 'fullName',
 *     'query_builder' => static fn (UserRepository $r) => $r->activeTechniciansQuery(),
 * ]);
 * ```
 *
 * ## Why not an `EntityType` with a Select2 URL
 *
 * That is what three back-offices did, and it only LOOKED lazy: the widget searched the API while
 * Symfony still loaded and wrote every row the `query_builder` allowed into the page — 801 options
 * on one work order picker, growing with the business. Here the choice list is empty, and the
 * current value is the only option written ({@see finishView()}).
 *
 * ## The `query_builder` still decides what may be posted
 *
 * ⚠️ It is no longer used to LIST, but it keeps its other job: {@see BoundedLazyChoiceLoader}
 * resolves the posted id through it. The API URL narrows what the user is offered; the query builder
 * is what the server accepts. Keep both saying the same thing — a URL wider than the query builder
 * offers rows the form will then refuse.
 *
 * ## The empty option is on by default
 *
 * `placeholder` defaults to `''`: the empty option it renders is what Select2's clear button
 * restores, and datatable-bundle ≥ 2.4.4 turns the button off when there is none. On a required
 * field, clearing then submitting is refused by the entity's `NotNull` — the form stays the gate.
 *
 * @extends AbstractType<mixed>
 */
final class AutocompleteEntityType extends AbstractType
{
    #[\Override]
    public function getParent(): string
    {
        return EntityType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('autocomplete_url');
        $resolver->setDefaults([
            'text_key' => 'name',
            // ⚠️ Not the Select2 controller's own default: it sends the term under `?<textKey>=`,
            // which no API resource exposes — they carry an OrSearchFilter under `?search=`.
            'search_key' => 'search',
            'secondary_key' => null,
            'depends_on' => null,
            'depends_param' => null,
            'placeholder' => '',
            'choice_loader' => static function (Options $options): BoundedLazyChoiceLoader {
                $idReader = $options['id_reader'];

                if (!$idReader instanceof IdReader || !$idReader->isSingleId()) {
                    throw new LogicException(\sprintf('"%s" needs an entity with a single identifier.', self::class));
                }

                $queryBuilder = $options['query_builder'];

                if (!$queryBuilder instanceof QueryBuilder) {
                    $manager = $options['em'];
                    $class = $options['class'];
                    \assert($manager instanceof EntityManagerInterface && \is_string($class) && class_exists($class));
                    $queryBuilder = $manager->getRepository($class)->createQueryBuilder('e');
                }

                return new BoundedLazyChoiceLoader(new ORMQueryBuilderLoader($queryBuilder), $idReader);
            },
        ]);
        $resolver->setAllowedTypes('autocomplete_url', 'string');
        $resolver->setAllowedTypes('text_key', 'string');
        $resolver->setAllowedTypes('search_key', 'string');
        $resolver->setAllowedTypes('secondary_key', ['null', 'string']);
        $resolver->setAllowedTypes('depends_on', ['null', 'string']);
        $resolver->setAllowedTypes('depends_param', ['null', 'string']);
    }

    /**
     * Wires the Select2 controller and writes the current value — the one option the page carries.
     *
     * @param array<string, mixed> $options
     */
    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var array<string, mixed> $attr */
        $attr = $view->vars['attr'] ?? [];

        $view->vars['attr'] = array_merge(array_filter([
            'data-controller' => 'ui--select2',
            'data-ui--select2-url-value' => $options['autocomplete_url'],
            'data-ui--select2-text-key-value' => $options['text_key'],
            'data-ui--select2-search-key-value' => $options['search_key'],
            'data-ui--select2-secondary-key-value' => $options['secondary_key'],
            'data-ui--select2-depends-on-value' => $options['depends_on'],
            'data-ui--select2-depends-param-value' => $options['depends_param'],
        ], static fn (mixed $value): bool => null !== $value), $attr);

        $idReader = $options['id_reader'];
        \assert($idReader instanceof IdReader);

        $current = $form->getData();
        $selected = is_iterable($current) ? [...$current] : [$current];

        $view->vars['choices'] = array_values(array_map(
            fn (object $entity): ChoiceView => new ChoiceView($entity, $idReader->getIdValue($entity), $this->labelOf($entity, $options['choice_label'])),
            array_filter($selected, \is_object(...)),
        ));
    }

    /**
     * The label of the current value, read as `choice_label` says — a property path, a callable, or
     * the entity's string form.
     */
    private function labelOf(object $entity, mixed $choiceLabel): string
    {
        $label = match (true) {
            \is_callable($choiceLabel) => $choiceLabel($entity, null, null),
            \is_string($choiceLabel) => PropertyAccess::createPropertyAccessor()->getValue($entity, $choiceLabel),
            $entity instanceof \Stringable => (string) $entity,
            default => null,
        };

        return \is_scalar($label) || $label instanceof \Stringable ? (string) $label : '';
    }
}
