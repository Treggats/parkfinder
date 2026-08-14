<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class PaginatorPayload
{
    public function __construct(
        public int $page,
        public int $total,
        public int $perPage,
        public int $lastPage,
        public int $currentItemCount,
        public ?int $nextPage,
        public ?int $previousPage,
        public array $items,
    ) {
    }
}
