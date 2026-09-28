<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\AbstractFunctionalTestCase;

final class CompanyControllerTest extends AbstractFunctionalTestCase
{
    public function testIndexEmptyState(): void
    {
        $crawler = $this->client->request('GET', '/companies');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Jelenleg nincsenek elérhető értékelések.', $crawler->text());
    }

    public function testStatisticsAggregationAndOrder(): void
    {
        $this->createReview('Acme', 4, 'Good', 'a1@x.com');
        $this->createReview('Acme', 2, 'Ok', 'a2@x.com');
        $this->createReview('Beta', 5, 'Great', 'b1@x.com');

        $crawler = $this->client->request('GET', '/companies');
        self::assertResponseIsSuccessful();

        $rows = $crawler->filter('table tbody tr');
        self::assertCount(2, $rows);

        $first = $rows->eq(0)->filter('td');
        self::assertStringContainsString('Beta', $first->eq(1)->text());
        self::assertStringContainsString('1', $first->eq(2)->text());
        self::assertStringContainsString('5,00', $first->eq(3)->text());

        $second = $rows->eq(1)->filter('td');
        self::assertStringContainsString('Acme', $second->eq(1)->text());
        self::assertStringContainsString('2', $second->eq(2)->text());
        self::assertStringContainsString('3,00', $second->eq(3)->text());
    }

    public function testSearchIsCaseInsensitiveAndFilters(): void
    {
        $this->createReview('FooCorp', 5, 'A', 'f1@x.com');
        $this->createReview('Bar Inc', 3, 'B', 'b1@x.com');

        $crawler = $this->client->request('GET', '/companies?search=fooc');
        self::assertResponseIsSuccessful();

        self::assertSame('fooc', $crawler->filter('input[name="search"]')->attr('value'));
        $rows = $crawler->filter('table tbody tr');
        self::assertCount(1, $rows);
        self::assertStringContainsString('FooCorp', $rows->first()->text());
    }

    public function testEmptySearchStringReturnsAllCompanies(): void
    {
        $this->createReview('One', 5, 'A', 'o1@x.com');
        $this->createReview('Two', 4, 'B', 't1@x.com');

        $crawler = $this->client->request('GET', '/companies?search=');
        self::assertResponseIsSuccessful();
        self::assertCount(2, $crawler->filter('table tbody tr'));
    }
}
