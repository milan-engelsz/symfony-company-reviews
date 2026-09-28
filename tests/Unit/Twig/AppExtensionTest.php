<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

final class AppExtensionTest extends TestCase
{
    public function testFormatDateTimeReturnsEmptyStringForNull(): void
    {
        $extension = new AppExtension();

        self::assertSame('', $extension->formatDateTime(null));
    }

    public function testFormatDateTimeFormatsWithConfiguredPattern(): void
    {
        $extension = new AppExtension();
        $date = new \DateTimeImmutable('2025-01-02 03:04:05');

        self::assertSame('2025-01-02 03:04', $extension->formatDateTime($date));
    }

    public function testGetFiltersRegistersAppDatetime(): void
    {
        $extension = new AppExtension();
        $filters = $extension->getFilters();

        self::assertCount(1, $filters);
        $filter = $filters[0];
        self::assertSame('app_datetime', $filter->getName());
        self::assertSame([$extension, 'formatDateTime'], $filter->getCallable());
    }
}
