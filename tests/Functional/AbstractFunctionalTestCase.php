<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Review;
use App\Tests\Support\DatabaseCleaner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractFunctionalTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $this->entityManager = $em;
        DatabaseCleaner::reset($this->entityManager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        static::ensureKernelShutdown();
    }

    /**
     * Persist a review via lifecycle (PrePersist sets createdAt/updatedAt).
     */
    protected function createReview(
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

    /**
     * Update persisted review timestamps (for ordering tests).
     */
    protected function setReviewTimestamps(
        int $reviewId,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): void {
        $table = $this->entityManager->getClassMetadata(Review::class)->getTableName();
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement(
            \sprintf('UPDATE %s SET created_at = :created, updated_at = :updated WHERE id = :id', $table),
            [
                'created' => $createdAt->format('Y-m-d H:i:s'),
                'updated' => $updatedAt->format('Y-m-d H:i:s'),
                'id' => $reviewId,
            ],
        );
        $this->entityManager->clear();
    }
}
