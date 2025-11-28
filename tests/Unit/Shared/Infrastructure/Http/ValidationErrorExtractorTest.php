<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\ValidationErrorExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class ValidationErrorExtractorTest extends TestCase
{
    public function testExtractEmptyViolationList(): void
    {
        $violations = new ConstraintViolationList();
        $errors = ValidationErrorExtractor::extract($violations);

        self::assertEmpty($errors);
        self::assertIsArray($errors);
    }

    public function testExtractSingleViolation(): void
    {
        $violation = new ConstraintViolation(
            'This field is required',
            null,
            [],
            null,
            'email',
            null,
        );

        $violations = new ConstraintViolationList([$violation]);
        $errors = ValidationErrorExtractor::extract($violations);

        self::assertCount(1, $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertSame('This field is required', $errors['email']);
    }

    public function testExtractMultipleViolations(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'Invalid email format',
                null,
                [],
                null,
                'email',
                null,
            ),
            new ConstraintViolation(
                'Password must be at least 8 characters',
                null,
                [],
                null,
                'password',
                null,
            ),
            new ConstraintViolation(
                'Username is already taken',
                null,
                [],
                null,
                'username',
                null,
            ),
        ]);

        $errors = ValidationErrorExtractor::extract($violations);

        self::assertCount(3, $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('password', $errors);
        self::assertArrayHasKey('username', $errors);
        self::assertSame('Invalid email format', $errors['email']);
        self::assertSame('Password must be at least 8 characters', $errors['password']);
        self::assertSame('Username is already taken', $errors['username']);
    }

    public function testExtractNestedPropertyPathViolations(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'Invalid value',
                null,
                [],
                null,
                'user.profile.name',
                null,
            ),
            new ConstraintViolation(
                'Must be positive',
                null,
                [],
                null,
                'settings[0].value',
                null,
            ),
        ]);

        $errors = ValidationErrorExtractor::extract($violations);

        self::assertCount(2, $errors);
        self::assertArrayHasKey('user.profile.name', $errors);
        self::assertArrayHasKey('settings[0].value', $errors);
        self::assertSame('Invalid value', $errors['user.profile.name']);
        self::assertSame('Must be positive', $errors['settings[0].value']);
    }

    public function testExtractViolationsOverwritesDuplicatePropertyPaths(): void
    {
        // If there are multiple violations for the same property, the last one wins
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'First error',
                null,
                [],
                null,
                'email',
                null,
            ),
            new ConstraintViolation(
                'Second error',
                null,
                [],
                null,
                'email',
                null,
            ),
        ]);

        $errors = ValidationErrorExtractor::extract($violations);

        self::assertCount(1, $errors);
        self::assertSame('Second error', $errors['email']);
    }
}
