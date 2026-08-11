<?php

declare(strict_types=1);

namespace App\Action;

use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class GenerateSlug
{
    public function __construct(
        private SluggerInterface $slugger,
    ) {
    }

    public function make(string $value): string
    {
        return $this->slugger->slug($value)->lower()->toString();
    }
}
