<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ParkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParkRepository::class)]
#[ORM\UniqueConstraint('unique_park_slug', columns: ['slug'])]
class Park
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private bool $hasPool = false;

    public function __construct(
        #[ORM\Column(length: 255)]
        private string $name,
        #[ORM\Column(length: 255)]
        private readonly string $slug,
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

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function hasPool(): bool
    {
        return $this->hasPool;
    }

    public function setHasPool(bool $hasPool): static
    {
        $this->hasPool = $hasPool;

        return $this;
    }
}
