<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public const DATETIME_FORMAT = 'Y-m-d H:i';

    public function getFilters(): array
    {
        return [
            new TwigFilter('app_datetime', [$this, 'formatDateTime']),
        ];
    }

    public function formatDateTime(?\DateTimeInterface $value): string
    {
        if (null === $value) {
            return '';
        }

        return $value->format(self::DATETIME_FORMAT);
    }
}
