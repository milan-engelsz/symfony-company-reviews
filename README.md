# Company reviews (Symfony take-home)

[![Tests](https://github.com/milan-engelsz/symfony-company-reviews/actions/workflows/tests.yml/badge.svg)](https://github.com/milan-engelsz/symfony-company-reviews/actions/workflows/tests.yml)

Symfony 7 app to submit and browse **company reviews** (rating, text, author email, company name). Home page: paginated review list. Separate views for company stats / search and single-review detail.

Hiring take-home. I had **not written Symfony before** this exercise; the submission led to a **senior-role interview**.

No Vue / SPA — server-rendered Twig.

## Stack

- PHP 8.2+ · Symfony 7.4
- Doctrine ORM · PostgreSQL (Docker Compose)
- Twig · Symfony Asset Mapper / Stimulus
- KnpPaginatorBundle · custom Twig `app_datetime` filter
- PHPUnit (unit + functional)

## Features

- Submit a review (form + validation: required fields, rating 1–5, email)
- Paginated review list (company, stars, truncated text, date)
- Review detail page
- Company stats: count + average rating, sorted by average desc
- Bonus: search by company name

## Run

```bash
git clone https://github.com/milan-engelsz/symfony-company-reviews.git
cd symfony-company-reviews
composer install
docker compose up -d
php bin/console doctrine:migrations:migrate
symfony serve:start
```

`.env` is committed with defaults. Override locally in `.env.local` only if `DATABASE_URL` (or other values) must differ from Compose.

## Tests / CI

```bash
composer test
composer format:check
```

GitHub Actions runs `composer test` on push / PR (see `.github/workflows/tests.yml`).

## Note

Interview exercise for a review-widget style brief — not a production product.
