<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * @template T of array
 */
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
        /** @var T $items */
        public array $items,
    ) {
    }
}
