<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An entity with a BOUND: only active people may be picked. The bound is what the autocomplete type
 * must keep — a lazy loader that resolved posted ids without it would accept an inactive person.
 */
#[ORM\Entity]
#[ORM\Table(name: 'person')]
class Person
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 120)]
        private string $name,
        #[ORM\Column]
        private bool $active = true,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
