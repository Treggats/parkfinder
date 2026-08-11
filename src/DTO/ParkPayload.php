<?php

declare(strict_types=1);

namespace App\DTO;

use App\Validator\UniquePark;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ParkPayload
{
    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Length(min: 3, max: 255)]
        #[UniquePark()]
        public string $name,
        public bool $hasPool = false,
    ) {
    }
}
