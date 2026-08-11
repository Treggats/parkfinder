<?php

declare(strict_types=1);

namespace App\Factory;

use App\Action\GenerateSlug;
use App\Entity\Park;

final readonly class ParkFactory
{
    public function __construct(
        private GenerateSlug $generateSlug,
    ) {
    }

    public function create(string $name, bool $hasPool = false): Park
    {
        $park = new Park(
            name: $name,
            slug: $this->generateSlug->make($name),
        );

        return $park->setHasPool($hasPool);
    }
}
