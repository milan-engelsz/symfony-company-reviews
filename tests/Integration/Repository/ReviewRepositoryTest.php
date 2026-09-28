<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use App\Tests\Support\DatabaseCleaner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReviewRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private ReviewRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $this->entityManager = $em;
        DatabaseCleaner::reset($this->entityManager);
        $this->repository = $em->getRepository(Review::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        self::ensureKernelShutdown();
    }

    public function testCreateOrderedByCreatedAtDescQueryBuilderSortsNewestFirst(): void
    {
        $old = $this->persistReview('Old', 3, 'Text', 'o@x.com');
        $new = $this->persistReview('New', 4, 'Text', 'n@x.com');
        $this->updateTimestamps($old->getId(), new \DateTimeImmutable('2000-01-01'));
        $this->updateTimestamps($new->getId(), new \DateTimeImmutable('2020-01-01'));

        $results = $this->repository->createOrderedByCreatedAtDescQueryBuilder()->getQuery()->getResult();
        self::assertCount(2, $results);
        self::assertSame('New', $results[0]->getCompanyName());
        self::assertSame('Old', $results[1]->getCompanyName());
    }

    public function testGetCompanyNamesWithStatisticsAggregatesAndOrdersByAverageDesc(): void
    {
        $this->persistReview('Acme', 4, 'A', 'a1@x.com');
        $this->persistReview('Acme', 2, 'B', 'a2@x.com');
        $this->persistReview('Beta', 5, 'C', 'b1@x.com');

        $rows = $this->repository->getCompanyNamesWithStatistics(null);
        self::assertCount(2, $rows);
        self::assertSame('Beta', $rows[0]['companyName']);
        self::assertSame(1, (int) $rows[0]['reviewCount']);
        self::assertEqualsWithDelta(5.0, (float) $rows[0]['avgRating'], 0.001);

        self::assertSame('Acme', $rows[1]['companyName']);
        self::assertSame(2, (int) $rows[1]['reviewCount']);
        self::assertEqualsWithDelta(3.0, (float) $rows[1]['avgRating'], 0.001);
    }

    public function testGetCompanyNamesWithStatisticsFiltersBySearchTerm(): void
    {
        $this->persistReview('Acme Ltd', 5, 'A', 'a@x.com');
        $this->persistReview('Other', 4, 'B', 'o@x.com');

        $rows = $this->repository->getCompanyNamesWithStatistics('acm');
        self::assertCount(1, $rows);
        self::assertSame('Acme Ltd', $rows[0]['companyName']);
    }

    public function testGetCompanyNamesWithStatisticsEmptySearchReturnsAll(): void
    {
        $this->persistReview('A', 5, 't', 'a@x.com');
        $this->persistReview('B', 4, 't', 'b@x.com');

        $rows = $this->repository->getCompanyNamesWithStatistics('');
        self::assertCount(2, $rows);
    }

    private function persistReview(
        string $companyName,
        int $rating,
        string $reviewText,
        string $authorEmail,
    ): Review {
        $review = (new Review())
            ->setCompanyName($companyName)
            ->setRating($rating)
            ->setReviewText($reviewText)
            ->setAuthorEmail($authorEmail);
        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $review;
    }

    private function updateTimestamps(int $reviewId, \DateTimeImmutable $createdAt): void
    {
        $table = $this->entityManager->getClassMetadata(Review::class)->getTableName();
        $this->entityManager->getConnection()->executeStatement(
            \sprintf('UPDATE %s SET created_at = :created, updated_at = :updated WHERE id = :id', $table),
            [
                'created' => $createdAt->format('Y-m-d H:i:s'),
                'updated' => $createdAt->format('Y-m-d H:i:s'),
                'id' => $reviewId,
            ],
        );
        $this->entityManager->clear();
    }
}
