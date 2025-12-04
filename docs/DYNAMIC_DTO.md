# Dynamic DTO with Shadow Classes

## Overview

This implementation provides a way to have **ONE strict-typed DTO class** without nullable properties, while still being able to deserialize and validate JSON data dynamically. It uses PHP reflection to create "shadow" classes with `mixed` type properties for deserialization and validation.

## The Problem

Traditional API DTOs face a dilemma:

```php
// Problem 1: Nullable properties everywhere
final readonly class CreateUserDto
{
    public function __construct(
        public ?string $username = null,  // Nullable for deserialization
        public ?string $email = null,     // Nullable for deserialization
        public ?int $age = null,          // Nullable for deserialization
    ) {}
}

// Problem 2: After validation, still need to check for nulls
public function create(CreateUserDto $dto): Response
{
    // $dto->username could still be null! Need to check everywhere
    if ($dto->username === null) {
        throw new \Exception('Username is required');
    }
}
```

## The Solution

With **Dynamic DTO**, you define ONE strict-typed class with validation constraints:

```php
final readonly class CreateUserDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 50)]
        public string $username,  // NOT nullable!

        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,  // NOT nullable!

        #[Assert\NotBlank]
        #[Assert\Range(min: 18, max: 120)]
        public int $age,  // NOT nullable!
    ) {}
}
```

Use it in controller with `#[DynamicDto]` attribute:

```php
public function create(
    #[DynamicDto] CreateUserDto $dto,
): Response {
    // $dto is guaranteed to be valid and non-null!
    // No need for null checks - type safety guaranteed
    $this->userService->create(
        $dto->username,  // string, never null
        $dto->email,     // string, never null
        $dto->age,       // int, never null
    );
}
```

## How It Works

### 1. Shadow Class Generation

When JSON arrives, the system:

1. **Analyzes your DTO** using reflection
2. **Creates a shadow class** at runtime with `mixed` properties
3. **Copies validation attributes** from your DTO to the shadow class

```php
// Your DTO
final readonly class CreateUserDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $username,
    ) {}
}

// Generated shadow class (at runtime)
namespace App\Generated\Shadow;

final class CreateUserDtoShadow
{
    #[Assert\NotBlank]  // Copied from original
    public mixed $username = null;  // Mixed type!
}
```

### 2. Deserialization

JSON is deserialized into the shadow object:

```json
{
  "username": "not-a-number-but-we-accept-it"
}
```

```php
// No type error because shadow property is mixed!
$shadow->username = "not-a-number-but-we-accept-it";
```

### 3. Validation

Symfony validator validates the shadow object:

```php
$violations = $validator->validate($shadow);

if ($violations->count() > 0) {
    throw ValidationException::fromConstraintViolationList($violations);
}
```

### 4. Mapping to Strict DTO

Only if validation passes, map to your strict DTO:

```php
$strictDto = new CreateUserDto(
    username: $shadow->username,  // Now guaranteed to be valid
);
```

## Complete Example

### 1. Define Strict DTO

```php
namespace App\BankAccount\Api\Frontend\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateAccountDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $customerId,

        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['CHECKING', 'SAVINGS', 'BUSINESS'])]
        public string $accountType,

        #[Assert\NotBlank]
        #[Assert\Currency]
        public string $currency,

        #[Assert\Type('numeric')]
        #[Assert\GreaterThanOrEqual(0)]
        public float $initialBalance,
    ) {}
}
```

### 2. Use in Controller

```php
use App\Shared\Infrastructure\Http\Attribute\DynamicDto;

final class BankAccountController
{
    #[Route('/api/accounts', methods: ['POST'])]
    public function create(
        #[DynamicDto] CreateAccountDto $dto,
    ): Response {
        // $dto is strictly typed and validated!

        $account = $this->accountService->create(
            customerId: $dto->customerId,      // string
            accountType: $dto->accountType,    // string
            currency: $dto->currency,          // string
            initialBalance: $dto->initialBalance,  // float
        );

        return new JsonResponse(['id' => $account->getId()]);
    }
}
```

### 3. Test with Valid Data

```bash
POST /api/accounts
Content-Type: application/json

{
  "customerId": "550e8400-e29b-41d4-a716-446655440000",
  "accountType": "CHECKING",
  "currency": "USD",
  "initialBalance": 1000.50
}
```

Response:
```json
{
  "id": "123e4567-e89b-12d3-a456-426614174000"
}
```

### 4. Test with Invalid Data

```bash
POST /api/accounts
Content-Type: application/json

{
  "customerId": "not-a-uuid",
  "accountType": "INVALID",
  "currency": "US",
  "initialBalance": -100
}
```

Response:
```json
{
  "errors": [
    {
      "path": "customerId",
      "message": "This is not a valid UUID."
    },
    {
      "path": "accountType",
      "message": "The value you selected is not a valid choice."
    },
    {
      "path": "currency",
      "message": "This value is not a valid currency."
    },
    {
      "path": "initialBalance",
      "message": "This value should be greater than or equal to 0."
    }
  ]
}
```

## Architecture

### Components

1. **`DynamicValidator`** - Core service that:
   - Creates shadow classes using reflection
   - Hydrates shadow objects from data
   - Validates shadow objects
   - Maps to strict DTOs

2. **`DynamicDtoValueResolver`** - Symfony argument resolver that:
   - Intercepts controller arguments with `#[DynamicDto]`
   - Extracts request data
   - Calls DynamicValidator
   - Injects validated DTO

3. **`DynamicDto`** - Attribute marking parameters for dynamic validation

### Flow Diagram

```
┌─────────────────┐
│  HTTP Request   │
│   (JSON Data)   │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────┐
│ DynamicDtoValueResolver     │
│ - Checks for #[DynamicDto]  │
│ - Extracts request data     │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│    DynamicValidator         │
│ 1. Create shadow class      │
│    (mixed properties)       │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ 2. Hydrate shadow object    │
│    with JSON data           │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ 3. Validate shadow object   │
│    (Symfony Validator)      │
└────────┬────────────────────┘
         │
    ┌────┴────┐
    │ Invalid?│
    └────┬────┘
         │ Yes
         ▼
┌──────────────────────────┐
│ ValidationException      │
│ - Return error response  │
└──────────────────────────┘
         │
         │ No (Valid)
         ▼
┌─────────────────────────────┐
│ 4. Map to strict DTO        │
│    (non-nullable properties)│
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│   Controller Action         │
│ - Receives validated DTO    │
│ - All properties guaranteed │
└─────────────────────────────┘
```

## Advanced Features

### Type Coercion

Shadow classes accept any type, allowing validation to handle type checking:

```php
final readonly class UserDto
{
    public function __construct(
        #[Assert\Type('integer')]
        #[Assert\Range(min: 18, max: 120)]
        public int $age,
    ) {}
}

// JSON with wrong type
{"age": "not-a-number"}

// Shadow class accepts it (mixed type)
// Validator rejects it (Type constraint)
// Result: Validation error, not PHP type error
```

### Complex Validation

Use any Symfony validation constraint:

```php
final readonly class ComplexDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[A-Z]{2}[0-9]{8}$/')]
        public string $accountNumber,

        #[Assert\NotBlank]
        #[Assert\DateTime(format: 'Y-m-d')]
        public string $dateOfBirth,

        #[Assert\NotBlank]
        #[Assert\Callback([self::class, 'validateCustomLogic'])]
        public string $customField,
    ) {}

    public static function validateCustomLogic($value, ExecutionContextInterface $context): void
    {
        if (/* custom logic */) {
            $context->buildViolation('Custom validation failed')
                ->addViolation();
        }
    }
}
```

### Direct Usage (Without Controller)

Use DynamicValidator directly in services:

```php
final readonly class ImportService
{
    public function __construct(
        private DynamicValidator $dynamicValidator,
    ) {}

    public function importUsers(array $data): void
    {
        foreach ($data as $row) {
            try {
                $dto = $this->dynamicValidator->validateAndHydrate(
                    CreateUserDto::class,
                    $row
                );

                $this->userService->create($dto);
            } catch (ValidationException $e) {
                $this->logger->error('Invalid user data', [
                    'errors' => $e->getErrorsAsArray(),
                    'data' => $row,
                ]);
            }
        }
    }
}
```

## Comparison with Other Approaches

### vs MapRequestPayload

```php
// MapRequestPayload - requires nullable properties
final readonly class CreateUserDto
{
    public ?string $username = null;  // Must be nullable
    public ?string $email = null;     // Must be nullable
}

// DynamicDto - strict types!
final readonly class CreateUserDto
{
    #[Assert\NotBlank]
    public string $username;  // NOT nullable

    #[Assert\NotBlank]
    public string $email;     // NOT nullable
}
```

### vs ValidatedInput (Previous Approach)

```php
// ValidatedInput - Need TWO classes
final class CreateUserInput extends ValidatedInput
{
    public ?string $username = null;
    public ?string $email = null;
}

final readonly class CreateUserData
{
    public string $username;
    public string $email;
}

// DynamicDto - ONE class
final readonly class CreateUserDto
{
    public string $username;
    public string $email;
}
```

## Testing

### Unit Tests

```php
public function testValidDataIsHydratedToStrictDto(): void
{
    $data = [
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'age' => 30,
    ];

    $dto = $this->dynamicValidator->validateAndHydrate(
        CreateUserDto::class,
        $data
    );

    self::assertInstanceOf(CreateUserDto::class, $dto);
    self::assertSame('johndoe', $dto->username);
    self::assertSame('john@example.com', $dto->email);
    self::assertSame(30, $dto->age);
}

public function testInvalidDataThrowsValidationException(): void
{
    $data = [
        'username' => '', // Invalid
        'email' => 'not-an-email', // Invalid
        'age' => 15, // Too young
    ];

    $this->expectException(ValidationException::class);

    $this->dynamicValidator->validateAndHydrate(
        CreateUserDto::class,
        $data
    );
}
```

## Performance Considerations

- **Shadow class creation**: Uses `eval()` but caches classes in memory
- **Reflection overhead**: Minimal - only on first request per DTO
- **Memory**: Shadow classes remain in memory but are lightweight
- **Production**: Consider pre-generating shadow classes for performance

## Best Practices

### 1. Use Readonly DTOs

```php
final readonly class CreateUserDto  // readonly!
{
    public function __construct(
        public string $username,
    ) {}
}
```

### 2. Place Validation on Constructor Parameters

```php
// Good - validation on parameters
public function __construct(
    #[Assert\NotBlank]
    public string $username,
) {}

// Avoid - validation on properties (won't be copied to shadow)
#[Assert\NotBlank]
public string $username;
```

### 3. Use Descriptive Validation Messages

```php
#[Assert\NotBlank(message: 'Username is required')]
#[Assert\Length(
    min: 3,
    max: 50,
    minMessage: 'Username must be at least {{ limit }} characters',
    maxMessage: 'Username cannot be longer than {{ limit }} characters'
)]
public string $username;
```

### 4. Group Related DTOs

```php
// backend/src/BankAccount/Api/Frontend/Dto/
CreateAccountDto.php
UpdateAccountDto.php
CloseAccountDto.php
```

## Limitations

1. **Constructor-based DTOs only** - DTOs must use constructor property promotion
2. **Public properties only** - Private/protected properties are not supported
3. **No inheritance** - Shadow class generation doesn't support inheritance
4. **PHP 8.1+** - Requires readonly properties and constructor promotion

## Migration Guide

### From ValidatedInput

**Before:**
```php
final class CreateUserInput extends ValidatedInput
{
    #[MapTo(CreateUserData::class)]
    public ?string $username = null;
}

final readonly class CreateUserData
{
    public string $username;
}
```

**After:**
```php
final readonly class CreateUserDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $username,
    ) {}
}

public function create(#[DynamicDto] CreateUserDto $dto): Response
```

## Related Documentation

- [API Implementation Guide](API_IMPLEMENTATION_GUIDE.md)
- [Symfony Validation](https://symfony.com/doc/current/validation.html)
- [PHP Attributes](https://www.php.net/manual/en/language.attributes.overview.php)

