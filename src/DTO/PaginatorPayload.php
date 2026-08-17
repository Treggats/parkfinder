<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * @template T of array
 */
final class PaginatorPayload
{
    public ?int $nextPage {
        get => ($this->page + 1) <= $this->lastPage ? $this->lastPage : null;
    }

    public ?int $previousPage {
        get => ($this->page - 1) >= 1 ? $this->page - 1 : null;
    }

    public int $lastPage {
        get => (int) ceil($this->total / $this->perPage);
    }

    public int $currentItemCount {
        get => count($this->items);
    }

    public function __construct(
        public readonly int $page,
        public readonly int $total,
        public readonly int $perPage,
        /** @var T $items */
        public readonly array $items,
    ) {
    }
}
