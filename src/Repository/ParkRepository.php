<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\PaginatorPayload;
use App\Entity\Park;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Park>
 */
final class ParkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Park::class);
    }

    /** @return PaginatorPayload<Park[]> */
    public function paginate(int $page, int $perPage = 25): PaginatorPayload
    {
        $total = $this->count();
        $lastPage = (int) ceil($total / $perPage);

        /** @var Park[] $parks */
        $parks = $this->findBy([], orderBy: ['id' => 'ASC'], limit: $perPage, offset: $perPage * ($page - 1));

        return new PaginatorPayload(
            page: $page,
            total: $total,
            perPage: $perPage,
            lastPage: $lastPage,
            currentItemCount: count($parks),
            nextPage: ($page + 1) <= $lastPage ? $page + 1 : null,
            previousPage: ($page - 1) >= 1 ? $page - 1 : null,
            items: $parks,
        );
    }
}
