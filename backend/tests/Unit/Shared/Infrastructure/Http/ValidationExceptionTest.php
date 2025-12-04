<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\ApiValidator;
use App\Shared\Infrastructure\Http\ValidationError;
use App\Shared\Infrastructure\Http\ValidationException;
use PHPUnit\Framework\TestCase;

final class ValidationExceptionTest extends TestCase
{
    public function testConstructorSetsErrorsAndMessage(): void
    {
        $errors = [
            new ValidationError('email', 'Invalid email'),
            new ValidationError('age', 'Must be positive'),
        ];

        $exception = new ValidationException($errors, 'Custom validation message');

        self::assertSame('Custom validation message', $exception->getMessage());
        self::assertSame($errors, $exception->getErrors());
    }

    public function testConstructorUsesDefaultMessage(): void
    {
        $errors = [
            new ValidationError('email', 'Invalid email'),
        ];

        $exception = new ValidationException($errors);

        self::assertSame('Validation failed', $exception->getMessage());
    }

    public function testGetErrorsAsArray(): void
    {
        $errors = [
            new ValidationError('email', 'Invalid email', 'test@'),
            new ValidationError('age', 'Must be positive', -5),
        ];

        $exception = new ValidationException($errors);
        $array = $exception->getErrorsAsArray();

        self::assertCount(2, $array);
        self::assertSame('email', $array[0]['path']);
        self::assertSame('Invalid email', $array[0]['message']);
        self::assertSame('test@', $array[0]['invalidValue']);
    }

    public function testGetErrorsAsFlatArray(): void
    {
        $errors = [
            new ValidationError('email', 'Invalid email'),
            new ValidationError('age', 'Must be positive'),
        ];

        $exception = new ValidationException($errors);
        $flatArray = $exception->getErrorsAsFlatArray();

        self::assertSame([
            'email' => 'Invalid email',
            'age' => 'Must be positive',
        ], $flatArray);
    }
}
