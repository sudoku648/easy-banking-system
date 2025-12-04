<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\ValidationError;
use PHPUnit\Framework\TestCase;

final class ValidationErrorTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $error = new ValidationError('email', 'This value is not a valid email', 'invalid@');

        self::assertSame('email', $error->getPath());
        self::assertSame('This value is not a valid email', $error->getMessage());
        self::assertSame('invalid@', $error->getInvalidValue());
    }

    public function testToArrayWithInvalidValue(): void
    {
        $error = new ValidationError('amount', 'This value should be positive', -10);

        $expected = [
            'path' => 'amount',
            'message' => 'This value should be positive',
            'invalidValue' => -10,
        ];

        self::assertSame($expected, $error->toArray());
    }

    public function testToArrayWithoutInvalidValue(): void
    {
        $error = new ValidationError('username', 'This field is required');

        $expected = [
            'path' => 'username',
            'message' => 'This field is required',
        ];

        self::assertSame($expected, $error->toArray());
    }

    public function testToArrayWithNullInvalidValue(): void
    {
        $error = new ValidationError('password', 'This field is required', null);

        $expected = [
            'path' => 'password',
            'message' => 'This field is required',
        ];

        self::assertSame($expected, $error->toArray());
    }

    public function testToArrayWithEmptyStringInvalidValue(): void
    {
        $error = new ValidationError('name', 'This field cannot be blank', '');

        $expected = [
            'path' => 'name',
            'message' => 'This field cannot be blank',
        ];

        self::assertSame($expected, $error->toArray());
    }

    public function testToArrayWithZeroInvalidValue(): void
    {
        $error = new ValidationError('count', 'This value should be greater than zero', 0);

        $expected = [
            'path' => 'count',
            'message' => 'This value should be greater than zero',
            'invalidValue' => 0,
        ];

        self::assertSame($expected, $error->toArray());
    }
}
