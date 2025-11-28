# API Response Models

This document describes the common API response models used throughout the application.

## Overview

All API endpoints should use standardized response models to ensure consistency across the API. The response models are located in `src/Shared/Infrastructure/Http/` and provide a unified structure for both success and error responses.

## Response Structure

All API responses follow this basic structure:

```json
{
  "status": "success|error",
  "message": "Human-readable message"
}
```

### Success Response

Success responses include an optional `data` field:

```json
{
  "status": "success",
  "message": "Operation completed successfully",
  "data": {
    "key": "value"
  }
}
```

### Error Response

Error responses include an optional `errors` field for validation errors:

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "fieldName": "Error message for this field"
  }
}
```

## Available Response Classes

### Success Responses
- `ApiSuccessResponse` - Generic success response with optional data

### Error Responses
- `ApiErrorResponse` - Generic error response (use when specific classes don't fit)
- `BadRequestResponse` - 400 Bad Request (validation errors, invalid input)
- `UnprocessableEntityResponse` - 422 Unprocessable Entity (domain exceptions, business rule violations)
- `InternalServerErrorResponse` - 500 Internal Server Error (unexpected errors)

## Usage

### ApiSuccessResponse

Use `ApiSuccessResponse` for successful operations:

```php
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use Symfony\Component\HttpFoundation\Response;

// Simple success response
return new ApiSuccessResponse(
    message: 'Operation completed successfully',
)->toJsonResponse();

// Success response with data
return new ApiSuccessResponse(
    message: 'User created successfully',
    data: [
        'userId' => '123',
        'email' => 'user@example.com',
    ],
)->toJsonResponse();

// Success response with custom status code
return new ApiSuccessResponse(
    message: 'Resource created',
    data: ['id' => '456'],
    statusCode: Response::HTTP_CREATED,
)->toJsonResponse();
```

### BadRequestResponse (400)

Use `BadRequestResponse` for validation errors and invalid input:

```php
use App\Shared\Infrastructure\Http\BadRequestResponse;

// Simple bad request
return new BadRequestResponse(
    message: 'Invalid JSON format',
)->toJsonResponse();

// Bad request with validation errors
return new BadRequestResponse(
    message: 'Validation failed',
    errors: [
        'email' => 'Invalid email format',
        'password' => 'Password must be at least 8 characters',
    ],
)->toJsonResponse();
```

### UnprocessableEntityResponse (422)

Use `UnprocessableEntityResponse` for domain exceptions and business rule violations:

```php
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;

// Domain exception (e.g., insufficient funds, account closed)
return new UnprocessableEntityResponse(
    message: 'Insufficient funds',
)->toJsonResponse();

// Business rule violation
return new UnprocessableEntityResponse(
    message: 'Daily withdrawal limit exceeded',
)->toJsonResponse();
```

### InternalServerErrorResponse (500)

Use `InternalServerErrorResponse` for unexpected errors:

```php
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;

// Default message
return new InternalServerErrorResponse()->toJsonResponse();

// Custom message
return new InternalServerErrorResponse(
    message: 'Database connection failed',
)->toJsonResponse();
```

### ApiErrorResponse (Generic)

Use `ApiErrorResponse` when you need a custom status code not covered by specific classes:

```php
use App\Shared\Infrastructure\Http\ApiErrorResponse;
use Symfony\Component\HttpFoundation\Response;

// Custom status code (e.g., 401, 403, 404)
return new ApiErrorResponse(
    message: 'Unauthorized access',
    statusCode: Response::HTTP_UNAUTHORIZED,
)->toJsonResponse();
```

### ValidationErrorExtractor

Use `ValidationErrorExtractor` to extract validation errors from Symfony's `ConstraintViolationListInterface`:

```php
use App\Shared\Infrastructure\Http\BadRequestResponse;
use App\Shared\Infrastructure\Http\ValidationErrorExtractor;

$violations = $this->validator->validate($dto);

if (\count($violations) > 0) {
    return new BadRequestResponse(
        message: 'Validation failed',
        errors: ValidationErrorExtractor::extract($violations),
    )->toJsonResponse();
}
```

## Example Controller

Here's a complete example of an API controller using the response models:

```php
<?php

declare(strict_types=1);

namespace App\YourContext\Api\Controller;

use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\BadRequestResponse;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\Shared\Infrastructure\Http\ValidationErrorExtractor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class YourApiController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Deserialize request
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                YourDto::class,
                'json',
            );
        } catch (NotEncodableValueException $e) {
            return new BadRequestResponse(
                message: 'Invalid JSON format',
            )->toJsonResponse();
        }

        // Validate DTO
        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            return new BadRequestResponse(
                message: 'Validation failed',
                errors: ValidationErrorExtractor::extract($violations),
            )->toJsonResponse();
        }

        try {
            // Your business logic here
            $result = $this->doSomething($dto);

            return new ApiSuccessResponse(
                message: 'Operation completed successfully',
                data: $result,
            )->toJsonResponse();
        } catch (\DomainException $e) {
            // Domain exceptions are business rule violations (422)
            return new UnprocessableEntityResponse(
                message: $e->getMessage(),
            )->toJsonResponse();
        } catch (\Exception $e) {
            // Unexpected errors (500)
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }
}
```

## HTTP Status Codes

Common HTTP status codes and when to use them:

- `200 OK` - Successful GET, PUT, PATCH, or DELETE request (use `ApiSuccessResponse`)
- `201 Created` - Successful POST request that creates a resource (use `ApiSuccessResponse`)
- `400 Bad Request` - Validation errors, malformed requests, invalid input (use `BadRequestResponse`)
- `401 Unauthorized` - Missing or invalid authentication (use `ApiErrorResponse`)
- `403 Forbidden` - Authenticated but not authorized to access resource (use `ApiErrorResponse`)
- `404 Not Found` - Resource not found (use `ApiErrorResponse`)
- `422 Unprocessable Entity` - Domain exceptions, business rule violations (use `UnprocessableEntityResponse`)
- `500 Internal Server Error` - Unexpected server errors (use `InternalServerErrorResponse`)

### Status Code Guidelines

**400 vs 422:**
- **400 (Bad Request)**: Use for **input validation** errors - malformed JSON, missing required fields, invalid data types, format violations
- **422 (Unprocessable Entity)**: Use for **domain/business rule** violations - insufficient funds, account closed, daily limit exceeded, etc.

## Best Practices

1. **Use specific response classes** - Prefer `BadRequestResponse`, `UnprocessableEntityResponse`, and `InternalServerErrorResponse` over the generic `ApiErrorResponse`
2. **400 for validation, 422 for domain errors** - Use `BadRequestResponse` for input validation and `UnprocessableEntityResponse` for business rule violations
3. **Provide clear messages** - Write user-friendly error messages that help developers understand what went wrong
4. **Include validation errors** - Always include field-specific errors for validation failures using `ValidationErrorExtractor`
5. **Handle exceptions properly** - Catch domain exceptions separately from unexpected exceptions
6. **Don't expose sensitive information** - Be careful not to leak internal details or stack traces in error messages
7. **Default messages for 500 errors** - Use the default message for `InternalServerErrorResponse` to avoid exposing implementation details

## Testing

The response models are thoroughly tested. See:
- `tests/Unit/Shared/Infrastructure/Http/ApiSuccessResponseTest.php`
- `tests/Unit/Shared/Infrastructure/Http/ApiErrorResponseTest.php`
- `tests/Unit/Shared/Infrastructure/Http/BadRequestResponseTest.php`
- `tests/Unit/Shared/Infrastructure/Http/UnprocessableEntityResponseTest.php`
- `tests/Unit/Shared/Infrastructure/Http/InternalServerErrorResponseTest.php`
- `tests/Unit/Shared/Infrastructure/Http/ValidationErrorExtractorTest.php`

When testing API endpoints, verify the response structure and status code:

```php
$this->assertResponseStatusCodeSame(422); // or 400, 500, etc.

$response = json_decode($client->getResponse()->getContent(), true);

self::assertSame('error', $response['status']);
self::assertSame('Expected error message', $response['message']);

// For success responses
self::assertSame('success', $response['status']);
self::assertSame('Expected message', $response['message']);
self::assertArrayHasKey('data', $response);
```
