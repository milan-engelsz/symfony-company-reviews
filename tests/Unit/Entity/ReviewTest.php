<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Review;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ReviewTest extends TestCase
{
    private ?ValidatorInterface $validator = null;

    private function validator(): ValidatorInterface
    {
        if (null === $this->validator) {
            $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        }

        return $this->validator;
    }

    /**
     * @return list<string>
     */
    private function violationMessagesForProperty(Review $review, string $propertyPath): array
    {
        $messages = [];

        foreach ($this->validator()->validate($review) as $violation) {
            if ($propertyPath === $violation->getPropertyPath()) {
                $messages[] = $violation->getMessage();
            }
        }

        return $messages;
    }

    /**
     * @param class-string $constraintClass
     */
    private function assertPropertyViolatedByConstraint(
        Review $review,
        string $propertyPath,
        string $constraintClass,
    ): void {
        foreach ($this->validator()->validate($review) as $violation) {
            if ($propertyPath !== $violation->getPropertyPath()) {
                continue;
            }
            $constraint = $violation->getConstraint();
            if ($constraint instanceof $constraintClass) {
                return;
            }
        }

        self::fail(sprintf(
            'Expected a violation on %s caused by %s.',
            $propertyPath,
            $constraintClass,
        ));
    }

    private function createValidReview(): Review
    {
        return (new Review())
            ->setCompanyName('Acme')
            ->setRating(3)
            ->setReviewText('abc')
            ->setAuthorEmail('a@b.co');
    }

    private function createReviewWithoutCompanyName(): Review
    {
        return (new Review())
            ->setRating(3)
            ->setReviewText('abc')
            ->setAuthorEmail('a@b.co');
    }

    private function createReviewWithoutRating(): Review
    {
        return (new Review())
            ->setCompanyName('Acme')
            ->setReviewText('abc')
            ->setAuthorEmail('a@b.co');
    }

    private function createReviewWithoutReviewText(): Review
    {
        return (new Review())
            ->setCompanyName('Acme')
            ->setRating(3)
            ->setAuthorEmail('a@b.co');
    }

    private function createReviewWithoutAuthorEmail(): Review
    {
        return (new Review())
            ->setCompanyName('Acme')
            ->setRating(3)
            ->setReviewText('abc');
    }

    public function testSetCreatedAtValueInitializesTimestamps(): void
    {
        $review = $this->createValidReview();
        $before = new \DateTimeImmutable();

        $review->setCreatedAtValue();

        self::assertNotNull($review->getCreatedAt());
        self::assertNotNull($review->getUpdatedAt());
        self::assertGreaterThanOrEqual($before, $review->getCreatedAt());
        self::assertEquals($review->getCreatedAt()->getTimestamp(), $review->getUpdatedAt()->getTimestamp());
    }

    public function testSetUpdatedAtValueOnlyUpdatesUpdatedAt(): void
    {
        $review = $this->createValidReview();
        $review->setCreatedAtValue();
        $createdAt = $review->getCreatedAt();
        $firstUpdated = $review->getUpdatedAt();
        self::assertNotNull($createdAt);
        self::assertNotNull($firstUpdated);

        $review->setUpdatedAtValue();

        self::assertSame($createdAt->getTimestamp(), $review->getCreatedAt()->getTimestamp());
        $secondUpdated = $review->getUpdatedAt();
        self::assertNotNull($secondUpdated);
        self::assertNotSame($firstUpdated, $secondUpdated);
        self::assertGreaterThanOrEqual(
            $firstUpdated->getTimestamp(),
            $secondUpdated->getTimestamp(),
        );
    }

    public function testValidationCompanyName(): void
    {
        $nullCompany = $this->createReviewWithoutCompanyName();
        self::assertNotEmpty($this->violationMessagesForProperty($nullCompany, 'companyName'));
        $this->assertPropertyViolatedByConstraint($nullCompany, 'companyName', NotBlank::class);

        $blank = $this->createValidReview()->setCompanyName('');
        self::assertNotEmpty($this->violationMessagesForProperty($blank, 'companyName'));
        $this->assertPropertyViolatedByConstraint($blank, 'companyName', NotBlank::class);

        $tooLong = $this->createValidReview()->setCompanyName(str_repeat('x', 256));
        self::assertNotEmpty($this->violationMessagesForProperty($tooLong, 'companyName'));
        $this->assertPropertyViolatedByConstraint($tooLong, 'companyName', Length::class);

        $ok = $this->createValidReview()->setCompanyName(str_repeat('x', 255));
        self::assertCount(0, $this->violationMessagesForProperty($ok, 'companyName'));
    }

    public function testValidationRating(): void
    {
        $nullRating = $this->createReviewWithoutRating();
        self::assertNotEmpty($this->violationMessagesForProperty($nullRating, 'rating'));
        $this->assertPropertyViolatedByConstraint($nullRating, 'rating', NotBlank::class);

        $zero = $this->createValidReview()->setRating(0);
        self::assertNotEmpty($this->violationMessagesForProperty($zero, 'rating'));
        $this->assertPropertyViolatedByConstraint($zero, 'rating', Range::class);

        $six = $this->createValidReview()->setRating(6);
        self::assertNotEmpty($this->violationMessagesForProperty($six, 'rating'));
        $this->assertPropertyViolatedByConstraint($six, 'rating', Range::class);

        for ($i = 1; $i <= 5; ++$i) {
            $ok = $this->createValidReview()->setRating($i);
            self::assertCount(0, $this->violationMessagesForProperty($ok, 'rating'), "Rating $i should be valid");
        }
    }

    public function testValidationReviewText(): void
    {
        $nullText = $this->createReviewWithoutReviewText();
        self::assertNotEmpty($this->violationMessagesForProperty($nullText, 'reviewText'));
        $this->assertPropertyViolatedByConstraint($nullText, 'reviewText', NotBlank::class);

        $short = $this->createValidReview()->setReviewText('ab');
        self::assertNotEmpty($this->violationMessagesForProperty($short, 'reviewText'));
        $this->assertPropertyViolatedByConstraint($short, 'reviewText', Length::class);

        $ok = $this->createValidReview()->setReviewText('abc');
        self::assertCount(0, $this->violationMessagesForProperty($ok, 'reviewText'));
    }

    public function testValidationAuthorEmail(): void
    {
        $nullEmail = $this->createReviewWithoutAuthorEmail();
        self::assertNotEmpty($this->violationMessagesForProperty($nullEmail, 'authorEmail'));
        $this->assertPropertyViolatedByConstraint($nullEmail, 'authorEmail', NotBlank::class);

        $invalid = $this->createValidReview()->setAuthorEmail('not-an-email');
        self::assertNotEmpty($this->violationMessagesForProperty($invalid, 'authorEmail'));
        $this->assertPropertyViolatedByConstraint($invalid, 'authorEmail', Email::class);

        $tooLongEmail = str_repeat('a', 251).'@x.co';
        $long = $this->createValidReview()->setAuthorEmail($tooLongEmail);
        self::assertGreaterThan(255, \strlen($tooLongEmail));
        self::assertNotEmpty($this->violationMessagesForProperty($long, 'authorEmail'));
        $this->assertPropertyViolatedByConstraint($long, 'authorEmail', Length::class);

        $ok = $this->createValidReview()->setAuthorEmail('user@example.com');
        self::assertCount(0, $this->violationMessagesForProperty($ok, 'authorEmail'));
    }
}
