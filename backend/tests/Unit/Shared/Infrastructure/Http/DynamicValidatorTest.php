<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\DynamicValidator;
use App\Shared\Infrastructure\Http\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class DynamicValidatorTest extends TestCase
{
    private DynamicValidator $dynamicValidator;

    protected function setUp(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $this->dynamicValidator = new DynamicValidator($validator);
    }

    public function testValidDataIsHydratedToStrictDto(): void
    {
        $data = [
            'name' => 'John Doe',
            'age' => 30,
            'email' => 'john@example.com',
        ];

        $dto = $this->dynamicValidator->validateAndHydrate(TestUserDto::class, $data);

        self::assertInstanceOf(TestUserDto::class, $dto);
        self::assertSame('John Doe', $dto->name);
        self::assertSame(30, $dto->age);
        self::assertSame('john@example.com', $dto->email);
    }

    public function testInvalidDataThrowsValidationException(): void
    {
        $data = [
            'name' => '', // Invalid: blank
            'age' => 15, // Invalid: too young
            'email' => 'invalid-email', // Invalid: not an email
        ];

        $this->expectException(ValidationException::class);

        $this->dynamicValidator->validateAndHydrate(TestUserDto::class, $data);
    }

    public function testMissingRequiredFieldThrowsValidationException(): void
    {
        $data = [
            'name' => 'John Doe',
            // Missing 'age' and 'email'
        ];

        $this->expectException(ValidationException::class);

        $this->dynamicValidator->validateAndHydrate(TestUserDto::class, $data);
    }

    public function testWrongTypeIsAcceptedByShadowButFailsValidation(): void
    {
        $data = [
            'name' => 'John Doe',
            'age' => 'not-a-number', // Wrong type - string instead of int
            'email' => 'john@example.com',
        ];

        // Should not throw type error because shadow class has mixed properties
        // But should fail validation because of Type constraint
        $this->expectException(ValidationException::class);

        $this->dynamicValidator->validateAndHydrate(TestUserDto::class, $data);
    }

    public function testComplexDtoWithNestedValidation(): void
    {
        $data = [
            'username' => 'johndoe',
            'password' => 'short', // Too short
            'age' => 25,
        ];

        $this->expectException(ValidationException::class);

        $this->dynamicValidator->validateAndHydrate(TestComplexDto::class, $data);
    }

    public function testValidComplexDto(): void
    {
        $data = [
            'username' => 'johndoe',
            'password' => 'secure-password-123',
            'age' => 25,
        ];

        $dto = $this->dynamicValidator->validateAndHydrate(TestComplexDto::class, $data);

        self::assertInstanceOf(TestComplexDto::class, $dto);
        self::assertSame('johndoe', $dto->username);
        self::assertSame('secure-password-123', $dto->password);
        self::assertSame(25, $dto->age);
    }

    public function testTypeCoercionFromStrings(): void
    {
        $data = [
            'name' => 'John Doe',
            'age' => '30', // String should be coerced to int
            'email' => 'john@example.com',
        ];

        $dto = $this->dynamicValidator->validateAndHydrate(TestUserDto::class, $data);

        self::assertInstanceOf(TestUserDto::class, $dto);
        self::assertSame('John Doe', $dto->name);
        self::assertSame(30, $dto->age);
        self::assertIsInt($dto->age); // Verify it's actually an int, not string
        self::assertSame('john@example.com', $dto->email);
    }

    public function testIntegerCoercion(): void
    {
        $data = [
            'count' => '42',
            'total' => '100',
        ];

        $dto = $this->dynamicValidator->validateAndHydrate(TestIntegerDto::class, $data);

        self::assertSame(42, $dto->count);
        self::assertSame(100, $dto->total);
        self::assertIsInt($dto->count);
        self::assertIsInt($dto->total);
    }

    public function testFloatCoercion(): void
    {
        $data = [
            'price' => '19.99',
            'discount' => '5.5',
        ];

        $dto = $this->dynamicValidator->validateAndHydrate(TestFloatDto::class, $data);

        self::assertSame(19.99, $dto->price);
        self::assertSame(5.5, $dto->discount);
        self::assertIsFloat($dto->price);
        self::assertIsFloat($dto->discount);
    }

    public function testBooleanCoercionTrue(): void
    {
        $testCases = [
            ['active' => 'true'],
            ['active' => '1'],
            ['active' => 'yes'],
            ['active' => 'on'],
        ];

        foreach ($testCases as $data) {
            $dto = $this->dynamicValidator->validateAndHydrate(TestBooleanDto::class, $data);
            self::assertTrue($dto->active, "Failed for value: {$data['active']}");
            self::assertIsBool($dto->active);
        }
    }

    public function testBooleanCoercionFalse(): void
    {
        $testCases = [
            ['active' => 'false'],
            ['active' => '0'],
            ['active' => 'no'],
            ['active' => 'off'],
            ['active' => ''],
        ];

        foreach ($testCases as $data) {
            $dto = $this->dynamicValidator->validateAndHydrate(TestBooleanDto::class, $data);
            self::assertFalse($dto->active, "Failed for value: {$data['active']}");
            self::assertIsBool($dto->active);
        }
    }

    public function testArrayCoercionFromCommaSeparatedString(): void
    {
        $data = [
            'tags' => 'php,symfony,testing',
        ];

        $dto = $this->dynamicValidator->validateAndHydrate(TestArrayDto::class, $data);

        self::assertIsArray($dto->tags);
        self::assertSame(['php', 'symfony', 'testing'], $dto->tags);
    }

    public function testInvalidStringForIntegerFailsValidation(): void
    {
        $data = [
            'name' => 'John Doe',
            'age' => 'not-a-number', // Invalid integer
            'email' => 'john@example.com',
        ];

        $this->expectException(ValidationException::class);

        $this->dynamicValidator->validateAndHydrate(TestUserDto::class, $data);
    }

    public function testFloatStringForIntegerFailsValidation(): void
    {
        $data = [
            'name' => 'John Doe',
            'age' => '3.41', // Float string should not be coerced to int
            'email' => 'john@example.com',
        ];

        $this->expectException(ValidationException::class);

        $this->dynamicValidator->validateAndHydrate(TestUserDto::class, $data);
    }

    public function testMixedTypesFromQueryParams(): void
    {
        // Simulates data from query parameters (all strings)
        $data = [
            'username' => 'johndoe',
            'password' => 'secure-password-123',
            'age' => '25', // String should be coerced to int
        ];

        $dto = $this->dynamicValidator->validateAndHydrate(TestComplexDto::class, $data);

        self::assertInstanceOf(TestComplexDto::class, $dto);
        self::assertSame('johndoe', $dto->username);
        self::assertSame('secure-password-123', $dto->password);
        self::assertSame(25, $dto->age);
        self::assertIsInt($dto->age);
    }
}

// Test DTOs

final readonly class TestUserDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 100)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Range(min: 18, max: 120)]
        #[Assert\Type('integer')]
        public int $age,
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
    ) {
    }
}

final readonly class TestComplexDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 50)]
        #[Assert\Regex(pattern: '/^[a-z0-9_]+$/')]
        public string $username,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 100)]
        public string $password,
        #[Assert\NotBlank]
        #[Assert\Range(min: 13, max: 100)]
        #[Assert\Type('integer')]
        public int $age,
    ) {
    }
}

final readonly class TestIntegerDto
{
    public function __construct(
        #[Assert\Type('integer')]
        public int $count,
        #[Assert\Type('integer')]
        public int $total,
    ) {
    }
}

final readonly class TestFloatDto
{
    public function __construct(
        #[Assert\Type('float')]
        public float $price,
        #[Assert\Type('float')]
        public float $discount,
    ) {
    }
}

final readonly class TestBooleanDto
{
    public function __construct(
        #[Assert\Type('boolean')]
        public bool $active,
    ) {
    }
}

final readonly class TestArrayDto
{
    public function __construct(
        #[Assert\Type('array')]
        public array $tags,
    ) {
    }
}
