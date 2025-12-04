<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\ValueObject;

use App\UserManagement\Domain\ValueObject\PostalCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class PostalCodeTest extends TestCase
{
    public function testCreateValidPostalCode(): void
    {
        $postalCode = PostalCode::fromString('12-345');

        self::assertSame('12-345', $postalCode->getValue());
    }

    #[DataProvider('invalidPostalCodeProvider')]
    public function testCreateInvalidPostalCodeThrowsException(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        PostalCode::fromString($value);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function invalidPostalCodeProvider(): array
    {
        return [
            'missing dash' => ['12345'],
            'too short' => ['1-234'],
            'too long' => ['123-4567'],
            'wrong format' => ['AB-123'],
            'empty string' => [''],
            'only dash' => ['-'],
        ];
    }

    public function testEqualsReturnsTrueForSamePostalCode(): void
    {
        $postalCode1 = PostalCode::fromString('12-345');
        $postalCode2 = PostalCode::fromString('12-345');

        self::assertTrue($postalCode1->equals($postalCode2));
    }

    public function testEqualsReturnsFalseForDifferentPostalCode(): void
    {
        $postalCode1 = PostalCode::fromString('12-345');
        $postalCode2 = PostalCode::fromString('67-890');

        self::assertFalse($postalCode1->equals($postalCode2));
    }
}
