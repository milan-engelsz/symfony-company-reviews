<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Review;
use App\Tests\Functional\AbstractFunctionalTestCase;

final class ReviewControllerTest extends AbstractFunctionalTestCase
{
    public function testIndexEmptyState(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Nincs megjeleníthető vélemény.', $crawler->text());
    }

    public function testIndexPaginationAndOrdering(): void
    {
        for ($i = 1; $i <= 12; ++$i) {
            $this->createReview('Co '.$i, 3, 'Some review text here', 'u'.$i.'@ex.com');
        }

        $crawler = $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertCount(10, $crawler->filter('table tbody tr'));

        $repo = $this->entityManager->getRepository(Review::class);
        /** @var Review $newest */
        $newest = $repo->findOneBy(['companyName' => 'Co 12']);
        /** @var Review $oldest */
        $oldest = $repo->findOneBy(['companyName' => 'Co 1']);
        self::assertNotNull($newest);
        self::assertNotNull($oldest);
        $this->setReviewTimestamps(
            (int) $newest->getId(),
            new \DateTimeImmutable('2030-01-01 12:00:00'),
            new \DateTimeImmutable('2030-01-01 12:00:00'),
        );
        $this->setReviewTimestamps(
            (int) $oldest->getId(),
            new \DateTimeImmutable('2010-01-01 12:00:00'),
            new \DateTimeImmutable('2010-01-01 12:00:00'),
        );

        $crawler = $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        $firstCompanyCell = $crawler->filter('table tbody tr')->first()->filter('td')->eq(0)->text();
        self::assertStringContainsString('Co 12', $firstCompanyCell);

        self::assertCount(10, $crawler->filter('table tbody tr'));
        self::assertGreaterThan(0, $crawler->filter('.pagination')->count());

        $crawler = $this->client->request('GET', '/?page=2');
        self::assertResponseIsSuccessful();
        self::assertCount(2, $crawler->filter('table tbody tr'));
    }

    public function testNewShowsFormFields(): void
    {
        $crawler = $this->client->request('GET', '/review/new');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('input[name="review[companyName]"]'));
        self::assertCount(1, $crawler->filter('input[name="review[rating]"]'));
        self::assertCount(1, $crawler->filter('textarea[name="review[reviewText]"]'));
        self::assertCount(1, $crawler->filter('input[name="review[authorEmail]"]'));
    }

    public function testNewPostsValidReview(): void
    {
        $crawler = $this->client->request('GET', '/review/new');
        $repo = $this->entityManager->getRepository(Review::class);

        self::assertSame(0, $repo->count([]));

        $form = $crawler->selectButton('Mentés')->form([
            'review[companyName]' => 'MegaCorp',
            'review[rating]' => '5',
            'review[reviewText]' => 'Great place',
            'review[authorEmail]' => 'fan@megacorp.test',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/');
        self::assertSame(1, $repo->count([]));

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert.alert-success', 'Köszönjük');
    }

    public function testNewRejectInvalidSubmission(): void
    {
        $crawler = $this->client->request('GET', '/review/new');
        $repo = $this->entityManager->getRepository(Review::class);

        $form = $crawler->selectButton('Mentés')->form([
            'review[companyName]' => '',
            'review[rating]' => '6',
            'review[reviewText]' => 'okay',
            'review[authorEmail]' => 'not-email',
        ]);
        $this->client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSame(0, $repo->count([]));
        self::assertGreaterThan(0, $this->client->getCrawler()->filter('.is-invalid')->count());
    }

    public function testShowDisplaysFormattedCreatedAt(): void
    {
        $review = $this->createReview('Shown Corp', 4, 'Nice', 'shown@corp.test');
        $this->setReviewTimestamps(
            (int) $review->getId(),
            new \DateTimeImmutable('2031-06-05 07:08:09'),
            new \DateTimeImmutable('2031-06-05 07:08:09'),
        );
        /** @var Review|null $fresh */
        $fresh = $this->entityManager->getRepository(Review::class)->find($review->getId());
        self::assertNotNull($fresh);

        $crawler = $this->client->request('GET', '/review/'.$fresh->getId());

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Shown Corp', $crawler->text());
        self::assertStringContainsString('shown@corp.test', $crawler->text());
        self::assertStringContainsString('2031-06-05 07:08', $crawler->text());
    }

    public function testShowMissingReviewReturns404(): void
    {
        $this->client->request('GET', '/review/922337203685477580');
        self::assertResponseStatusCodeSame(404);
    }
}
