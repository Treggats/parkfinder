<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Park;
use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class ParkFactory
{
    public function __construct(
        private SluggerInterface $slugger,
    ) {
    }

    public function create(string $name, bool $hasPool = false): Park
    {
        $park = new Park(
            name: $name,
            slug: $this->slugger->slug($name)->lower()->toString(),
        );

        return $park->setHasPool($hasPool);
    }
}
