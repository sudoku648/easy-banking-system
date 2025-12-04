# API Validator - Usage Guide

## Overview

The API Validator provides a structured way to handle validation in API controllers, returning detailed validation errors with property paths and invalid values. This makes it easier for frontend applications to display validation errors to users.

## Core Components

### 1. `ApiValidator`
Main service for validating objects and getting structured validation errors.

### 2. `ValidationError`
Value object representing a single validation error with:
- `path`: Property path (e.g., `email`, `address.city`)
- `message`: Human-readable error message
- `invalidValue`: The value that failed validation (optional)

### 3. `ValidationException`
Exception thrown when validation fails, containing all validation errors.

### 4. `ValidationErrorResponse`
Response class for returning validation errors as JSON.

## Usage Examples

### Basic Usage in Controller

```php
use App\Shared\Infrastructure\Http\ApiValidator;
use App\Shared\Infrastructure\Http\ValidationErrorResponse;

final class CreateUserController extends AbstractController
{
    public function __construct(
        private readonly ApiValidator $apiValidator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $dto = $this->deserializeRequest($request, CreateUserDto::class);

        // Validate and get errors
        $errors = $this->apiValidator->validate($dto);

        if ([] !== $errors) {
            return (new ValidationErrorResponse($errors))
                ->toJsonResponse();
        }

        // Continue with business logic...
    }
}
```

### Using validateOrThrow() with Try-Catch

```php
use App\Shared\Infrastructure\Http\ApiValidator;
use App\Shared\Infrastructure\Http\ValidationErrorResponse;
use App\Shared\Infrastructure\Http\ValidationException;

final class CreateUserController extends AbstractController
{
    public function __construct(
        private readonly ApiValidator $apiValidator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = $this->deserializeRequest($request, CreateUserDto::class);

            // Throws ValidationException if validation fails
            $this->apiValidator->validateOrThrow($dto);

            // Continue with business logic...

        } catch (ValidationException $e) {
            return ValidationErrorResponse::fromException($e)
                ->toJsonResponse();
        }
    }
}
```

### Validation with Groups

```php
// Validate only specific validation groups
$errors = $this->apiValidator->validate($dto, ['registration']);

if ([] !== $errors) {
    return (new ValidationErrorResponse($errors))
        ->toJsonResponse();
}
```

### Global Exception Handler

You can create a global exception handler for `ValidationException`:

```php
// In your EventSubscriber or Exception Listener
use App\Shared\Infrastructure\Http\ValidationException;
use App\Shared\Infrastructure\Http\ValidationErrorResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ValidationExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof ValidationException) {
            return;
        }

        $response = ValidationErrorResponse::fromException($exception)
            ->toJsonResponse();

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
```

## Response Format

### Successful Validation
When validation passes, no response is returned - proceed with business logic.

### Failed Validation
When validation fails, the response format is:

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": {
      "message": "This value is not a valid email address.",
      "invalidValue": "invalid@"
    },
    "amount": {
      "message": "This value should be positive.",
      "invalidValue": -100
    },
    "username": {
      "message": "This value should not be blank."
    }
  }
}
```

**Status Code**: `400 Bad Request`

### Error Structure
Each error contains:
- `message`: Human-readable error description
- `invalidValue`: (optional) The value that failed validation (omitted for null/empty strings)

## DTO Example

```php
use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserDto
{
    #[Assert\NotBlank(message: 'Username is required')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Username must be at least {{ limit }} characters',
        maxMessage: 'Username cannot be longer than {{ limit }} characters'
    )]
    public string $username = '';

    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please provide a valid email address')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Password must be at least {{ limit }} characters'
    )]
    public string $password = '';

    #[Assert\Positive(message: 'Age must be a positive number')]
    public ?int $age = null;
}
```

## Advanced: Custom Validation Messages

You can customize validation messages in your constraints:

```php
#[Assert\NotBlank(message: 'The {{ label }} field is required')]
#[Assert\Length(
    min: 2,
    max: 50,
    minMessage: 'The {{ label }} must be at least {{ limit }} characters',
    maxMessage: 'The {{ label }} cannot exceed {{ limit }} characters'
)]
public string $firstName = '';
```

## Migrating from Old ValidationErrorExtractor

### Old Way (deprecated)
```php
use App\Shared\Infrastructure\Http\ValidationErrorExtractor;

$violations = $this->validator->validate($dto);
if (count($violations) > 0) {
    return new BadRequestResponse(
        message: 'Validation failed',
        errors: ValidationErrorExtractor::extract($violations),
    )->toJsonResponse();
}
```

### New Way (recommended)
```php
use App\Shared\Infrastructure\Http\ApiValidator;
use App\Shared\Infrastructure\Http\ValidationErrorResponse;

$errors = $this->apiValidator->validate($dto);
if ([] !== $errors) {
    return (new ValidationErrorResponse($errors))
        ->toJsonResponse();
}
```

## Benefits

1. **Structured Errors**: Clear property paths for easy frontend mapping
2. **Invalid Values**: Helps debugging by showing what value was rejected
3. **Consistent Format**: Standardized error response across all API endpoints
4. **Type Safety**: Full type hints and IDE support
5. **Flexible**: Works with Symfony validation groups
6. **Exception Support**: Can throw exceptions for cleaner code flow

## Configuration

The `ApiValidator` is automatically available as a service. Inject it via constructor:

```php
public function __construct(
    private readonly ApiValidator $apiValidator,
) {
}
```

No additional configuration required - it uses the standard Symfony Validator service.
