# API Validator - Quick Reference

## Files Created

### Core Classes
- `ApiValidator.php` - Main validator service
- `ValidationError.php` - Value object for validation errors
- `ValidationException.php` - Exception for validation failures
- `ValidationErrorResponse.php` - JSON response for validation errors
- `ValidationErrorExtractor.php` - Enhanced with `extractDetailed()` method (backward compatible)

### Tests
- `ValidationErrorTest.php` - Unit tests for ValidationError
- `ApiValidatorTest.php` - Unit tests for ApiValidator
- `ValidationExceptionTest.php` - Unit tests for ValidationException

### Documentation & Examples
- `docs/API_VALIDATOR.md` - Complete usage guide
- `ExampleValidationController.php` - Reference implementation with 3 examples

## Quick Usage

### 1. Basic Usage
```php
public function __construct(
    private readonly ApiValidator $apiValidator,
) {}

public function __invoke(Request $request): JsonResponse
{
    $errors = $this->apiValidator->validate($dto);

    if ([] !== $errors) {
        return (new ValidationErrorResponse($errors))
            ->toJsonResponse();
    }

    // Continue...
}
```

### 2. With Exception
```php
try {
    $this->apiValidator->validateOrThrow($dto);
    // Continue...
} catch (ValidationException $e) {
    return ValidationErrorResponse::fromException($e)
        ->toJsonResponse();
}
```

## Response Format

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
    }
  }
}
```

## Key Features

✅ **Property Paths** - Shows exact field that failed (e.g., `address.city`)
✅ **Invalid Values** - Includes the rejected value for debugging
✅ **Structured Format** - Consistent JSON structure across all endpoints
✅ **Type Safe** - Full PHP type hints
✅ **Backward Compatible** - Old `ValidationErrorExtractor` still works
✅ **Validation Groups** - Supports Symfony validation groups
✅ **Nested Objects** - Handles nested validation with proper paths

## Service Configuration

The `ApiValidator` is auto-wired - just inject it:

```php
public function __construct(
    private readonly ApiValidator $apiValidator,
) {}
```

## Migration from Old Code

### Before
```php
$violations = $this->validator->validate($dto);
if (count($violations) > 0) {
    return new BadRequestResponse(
        message: 'Validation failed',
        errors: ValidationErrorExtractor::extract($violations),
    )->toJsonResponse();
}
```

### After
```php
$errors = $this->apiValidator->validate($dto);
if ([] !== $errors) {
    return (new ValidationErrorResponse($errors))
        ->toJsonResponse();
}
```

## Testing

Run tests:
```bash
make test-unit-filter FILTER="ValidationError"
make test-unit-filter FILTER="ApiValidator"
make test-unit-filter FILTER="ValidationException"
```

## See Also

- Full documentation: `backend/docs/API_VALIDATOR.md`
- Example controller: `backend/src/BankAccount/Api/Frontend/Example/ExampleValidationController.php`
- Tests: `backend/tests/Unit/Shared/Infrastructure/Http/`
