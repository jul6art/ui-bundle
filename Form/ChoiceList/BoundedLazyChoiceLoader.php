<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\ChoiceList;

use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityLoaderInterface;
use Symfony\Bridge\Doctrine\Form\ChoiceList\IdReader;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * A choice loader that never loads the whole list, and never loads outside the field's bound.
 *
 * - **The list is empty.** What the widget offers comes from the API as the user types; the page
 *   only carries the current value, set by {@see \Jul6Art\UiBundle\Form\Type\AutocompleteEntityType}.
 * - **Posted ids are resolved through the field's own query.** The entity loader is Doctrine's
 *   `ORMQueryBuilderLoader` around the field's `query_builder`: `getEntitiesByIds()` adds an
 *   `IN (:ids)` to THAT query. A row that exists but that the query excludes — an inactive account,
 *   another customer's site — is not found, so the form refuses it exactly as the full list did.
 *
 * ⚠️ A bare `findBy(['id' => $ids])` would drop that bound: validation compares the posted value to
 * what this loader resolves, never to the `<option>` written in the page.
 */
final readonly class BoundedLazyChoiceLoader implements ChoiceLoaderInterface
{
    public function __construct(
        private EntityLoaderInterface $loader,
        private IdReader $idReader,
    ) {
    }

    #[\Override]
    public function loadChoiceList(?callable $value = null): ChoiceListInterface
    {
        return new ArrayChoiceList([], $value);
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return list<object>
     */
    #[\Override]
    public function loadChoicesForValues(array $values, ?callable $value = null): array
    {
        $ids = array_values(array_filter(
            array_map(static fn (mixed $raw): string => \is_scalar($raw) ? (string) $raw : '', $values),
            static fn (string $id): bool => '' !== $id,
        ));

        if ([] === $ids) {
            return [];
        }

        $found = [];
        foreach (array_filter($this->loader->getEntitiesByIds($this->idReader->getIdField(), $ids), \is_object(...)) as $entity) {
            $found[$this->idReader->getIdValue($entity)] = $entity;
        }

        // In the order of the posted values — what Symfony expects of a loader.
        return array_values(array_filter(array_map(static fn (string $id): ?object => $found[$id] ?? null, $ids)));
    }

    /**
     * @param array<array-key, mixed> $choices
     *
     * @return list<string>
     */
    #[\Override]
    public function loadValuesForChoices(array $choices, ?callable $value = null): array
    {
        return array_values(array_map(
            fn (mixed $choice): string => \is_object($choice) ? $this->idReader->getIdValue($choice) : '',
            $choices,
        ));
    }
}
