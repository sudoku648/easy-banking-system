<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\ApiValidator;
use App\Shared\Infrastructure\Http\ValidationError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class ApiValidatorTest extends TestCase
{
    private ApiValidator $apiValidator;

    protected function setUp(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $this->apiValidator = new ApiValidator($validator);
    }

    public function testValidateReturnsEmptyArrayForValidObject(): void
    {
        $dto = new class() {
            #[Assert\NotBlank]
            #[Assert\Email]
            public string $email = 'test@example.com';
        };

        $errors = $this->apiValidator->validate($dto);

        self::assertEmpty($errors);
    }

    public function testValidateReturnsErrorsForInvalidObject(): void
    {
        $dto = new class() {
            #[Assert\NotBlank]
            #[Assert\Email]
            public string $email = '';
        };

        $errors = $this->apiValidator->validate($dto);

        self::assertCount(1, $errors);
        self::assertInstanceOf(ValidationError::class, $errors[0]);
        self::assertSame('email', $errors[0]->getPath());
        self::assertStringContainsString('should not be blank', $errors[0]->getMessage());
    }

    public function testValidateReturnsMultipleErrors(): void
    {
        $dto = new class() {
            #[Assert\NotBlank]
            #[Assert\Length(min: 5)]
            public string $username = '';

            #[Assert\NotBlank]
            #[Assert\Email]
            public string $email = 'invalid';
        };

        $errors = $this->apiValidator->validate($dto);

        self::assertCount(3, $errors); // username: not blank, username: min length, email: not valid email

        $paths = array_map(fn (ValidationError $e): string => $e->getPath(), $errors);
        self::assertContains('username', $paths);
        self::assertContains('email', $paths);
    }

    public function testErrorsToArray(): void
    {
        $errors = [
            new ValidationError('email', 'Invalid email', 'test@'),
            new ValidationError('age', 'Must be positive', -5),
        ];

        $array = ApiValidator::errorsToArray($errors);

        self::assertCount(2, $array);
        self::assertSame('email', $array[0]['path']);
        self::assertSame('Invalid email', $array[0]['message']);
        self::assertSame('test@', $array[0]['invalidValue']);
        self::assertSame('age', $array[1]['path']);
        self::assertSame('Must be positive', $array[1]['message']);
        self::assertSame(-5, $array[1]['invalidValue']);
    }

    public function testErrorsToFlatArray(): void
    {
        $errors = [
            new ValidationError('email', 'Invalid email'),
            new ValidationError('age', 'Must be positive'),
        ];

        $flatArray = ApiValidator::errorsToFlatArray($errors);

        self::assertSame([
            'email' => 'Invalid email',
            'age' => 'Must be positive',
        ], $flatArray);
    }

    public function testValidateWithInvalidValueCapture(): void
    {
        $dto = new class() {
            #[Assert\Positive]
            public int $amount = -100;
        };

        $errors = $this->apiValidator->validate($dto);

        self::assertCount(1, $errors);
        self::assertSame('amount', $errors[0]->getPath());
        self::assertSame(-100, $errors[0]->getInvalidValue());
    }
}
