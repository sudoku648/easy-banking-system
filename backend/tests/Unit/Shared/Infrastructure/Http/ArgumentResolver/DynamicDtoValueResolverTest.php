<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\ArgumentResolver;

use App\Shared\Infrastructure\Http\ArgumentResolver\DynamicDtoValueResolver;
use App\Shared\Infrastructure\Http\Attribute\DynamicDto;
use App\Shared\Infrastructure\Http\DynamicValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class DynamicDtoValueResolverTest extends TestCase
{
    private DynamicDtoValueResolver $resolver;

    protected function setUp(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $dynamicValidator = new DynamicValidator($validator);
        $this->resolver = new DynamicDtoValueResolver($dynamicValidator);
    }

    public function testResolvesFromJsonBody(): void
    {
        $request = Request::create(
            '/api/test',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'John Doe',
                'age' => 30,
            ]),
        );

        $argument = new ArgumentMetadata(
            'dto',
            TestDto::class,
            false,
            false,
            null,
            false,
            [new DynamicDto()],
        );

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertCount(1, $result);
        self::assertInstanceOf(TestDto::class, $result[0]);
        self::assertSame('John Doe', $result[0]->name);
        self::assertSame(30, $result[0]->age);
    }

    public function testResolvesFromQueryParameters(): void
    {
        $request = Request::create(
            '/api/test?name=Jane+Doe&age=25',
            'GET',
        );

        $argument = new ArgumentMetadata(
            'dto',
            TestDto::class,
            false,
            false,
            null,
            false,
            [new DynamicDto()],
        );

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertCount(1, $result);
        self::assertInstanceOf(TestDto::class, $result[0]);
        self::assertSame('Jane Doe', $result[0]->name);
        self::assertSame(25, $result[0]->age); // String '25' should be coerced to int
        self::assertIsInt($result[0]->age); // Verify it's actually an int
    }

    public function testBodyTakesPrecedenceOverQueryParameters(): void
    {
        $request = Request::create(
            '/api/test?age=100&extra=value',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Override Name',
                'age' => 50,
            ]),
        );

        $argument = new ArgumentMetadata(
            'dto',
            TestDto::class,
            false,
            false,
            null,
            false,
            [new DynamicDto()],
        );

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertCount(1, $result);
        self::assertInstanceOf(TestDto::class, $result[0]);
        self::assertSame('Override Name', $result[0]->name);
        self::assertSame(50, $result[0]->age); // From body, not query (100)
    }

    public function testMergesQueryParametersAndBody(): void
    {
        $request = Request::create(
            '/api/test?age=35',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Mixed Data',
            ]),
        );

        $argument = new ArgumentMetadata(
            'dto',
            TestDto::class,
            false,
            false,
            null,
            false,
            [new DynamicDto()],
        );

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertCount(1, $result);
        self::assertInstanceOf(TestDto::class, $result[0]);
        self::assertSame('Mixed Data', $result[0]->name); // From body
        self::assertSame(35, $result[0]->age); // From query
    }

    public function testReturnsEmptyWhenNoDynamicDtoAttribute(): void
    {
        $request = Request::create('/api/test', 'GET');

        $argument = new ArgumentMetadata(
            'dto',
            TestDto::class,
            false,
            false,
            null,
            false,
            [], // No DynamicDto attribute
        );

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertEmpty($result);
    }

    public function testPaginationWithQueryParameters(): void
    {
        $request = Request::create(
            '/api/transactions?accountId=550e8400-e29b-41d4-a716-446655440000&page=2&limit=20',
            'GET',
        );

        $argument = new ArgumentMetadata(
            'dto',
            PaginationDto::class,
            false,
            false,
            null,
            false,
            [new DynamicDto()],
        );

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertCount(1, $result);
        self::assertInstanceOf(PaginationDto::class, $result[0]);
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $result[0]->accountId);
        self::assertSame(2, $result[0]->page); // String '2' coerced to int
        self::assertSame(20, $result[0]->limit); // String '20' coerced to int
        self::assertIsInt($result[0]->page);
        self::assertIsInt($result[0]->limit);
    }

    public function testTypeCoercionFromQueryParameters(): void
    {
        $request = Request::create(
            '/api/test?name=Type+Test&age=30&active=true&score=95.5',
            'GET',
        );

        $argument = new ArgumentMetadata(
            'dto',
            TypeCoercionDto::class,
            false,
            false,
            null,
            false,
            [new DynamicDto()],
        );

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertCount(1, $result);
        self::assertInstanceOf(TypeCoercionDto::class, $result[0]);
        
        // Verify types are correctly coerced
        self::assertSame('Type Test', $result[0]->name);
        self::assertSame(30, $result[0]->age);
        self::assertTrue($result[0]->active);
        self::assertSame(95.5, $result[0]->score);
        
        // Verify actual types
        self::assertIsString($result[0]->name);
        self::assertIsInt($result[0]->age);
        self::assertIsBool($result[0]->active);
        self::assertIsFloat($result[0]->score);
    }

    public function testBooleanCoercionVariants(): void
    {
        // Test various boolean representations
        $testCases = [
            ['active=true', true],
            ['active=false', false],
            ['active=1', true],
            ['active=0', false],
            ['active=yes', true],
            ['active=no', false],
            ['active=on', true],
            ['active=off', false],
        ];

        foreach ($testCases as [$query, $expected]) {
            $request = Request::create(
                "/api/test?name=Test&age=25&{$query}&score=1.0",
                'GET',
            );

            $argument = new ArgumentMetadata(
                'dto',
                TypeCoercionDto::class,
                false,
                false,
                null,
                false,
                [new DynamicDto()],
            );

            $result = iterator_to_array($this->resolver->resolve($request, $argument));

            self::assertSame($expected, $result[0]->active, "Failed for query: {$query}");
        }
    }
}

// Test DTOs
final readonly class TestDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        #[Assert\Type('integer')]
        #[Assert\GreaterThan(0)]
        public int $age,
    ) {
    }
}

final readonly class PaginationDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $accountId,
        #[Assert\Type('integer')]
        #[Assert\GreaterThan(0)]
        public int $page = 1,
        #[Assert\Type('integer')]
        #[Assert\Choice(choices: [10, 20, 50])]
        public int $limit = 10,
    ) {
    }
}

final readonly class TypeCoercionDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        #[Assert\Type('integer')]
        #[Assert\GreaterThan(0)]
        public int $age,
        #[Assert\Type('boolean')]
        public bool $active,
        #[Assert\Type('float')]
        public float $score,
    ) {
    }
}
