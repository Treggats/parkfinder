<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Park
{
    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Length(min: 3, max: 255)]
        public string $name,
        public bool $hasPool = false,
    ) {
    }
}
