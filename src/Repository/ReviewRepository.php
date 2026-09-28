<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function createOrderedByCreatedAtDescQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC');
    }

    /**
     * @return list<array{
     *     companyName: string,
     *     reviewCount: int,
     *     avgRating: float|string
     * }>
     */
    public function getCompanyNamesWithStatistics(?string $search = null): array
    {
        $query = $this->createQueryBuilder('r')
            ->select('r.companyName AS companyName', 'COUNT(r.id) AS reviewCount', 'AVG(r.rating) AS avgRating')
            ->groupBy('r.companyName')
            ->orderBy('avgRating', 'DESC');

        if ($search) {
            $query
                ->andWhere('LOWER(r.companyName) LIKE :term')
                ->setParameter('term', '%'.mb_strtolower($search).'%');
        }

        return $query->getQuery()->getResult();
    }
}
