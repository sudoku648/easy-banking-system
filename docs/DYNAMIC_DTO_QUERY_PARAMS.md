# DynamicDto Query Parameter Support

## Summary

Enhanced the `DynamicDto` feature to support extracting data from query parameters in addition to request body data. This allows for more flexible API design where the same DTO can handle:
- GET requests with query parameters
- POST requests with JSON body
- Mixed requests (query + body)

## Changes Made

### 1. Updated `DynamicDtoValueResolver`

**File:** `backend/src/Shared/Infrastructure/Http/ArgumentResolver/DynamicDtoValueResolver.php`

**Change:** Modified `getRequestData()` method to merge query parameters with request body data.

**Merge Strategy:**
- Query parameters are extracted first
- Request body data is extracted second
- **Body data takes precedence** over query parameters when keys conflict

```php
// Before (body only)
private function getRequestData(Request $request): array
{
    if ('json' === $request->getContentTypeFormat()) {
        // ... extract JSON
        return $data;
    }
    return $request->request->all();
}

// After (query + body)
private function getRequestData(Request $request): array
{
    $bodyData = [];
    
    if ('json' === $request->getContentTypeFormat()) {
        // ... extract JSON
        $bodyData = $data;
    } else {
        $bodyData = $request->request->all();
    }
    
    // Merge: body takes precedence over query
    return array_merge($request->query->all(), $bodyData);
}
```

### 2. Updated Documentation

**File:** `docs/DYNAMIC_DTO.md`

Added comprehensive section explaining:
- How query parameters work with DynamicDto
- Request examples (GET, POST, mixed)
- Merge strategy explanation
- Use cases and best practices

### 3. Added Test Coverage

**File:** `backend/tests/Unit/Shared/Infrastructure/Http/ArgumentResolver/DynamicDtoValueResolverTest.php`

Created comprehensive test suite covering:
- Resolving from JSON body only
- Resolving from query parameters only
- Body taking precedence over query parameters
- Merging query and body data
- Pagination example with query parameters

### 4. Example DTO

**File:** `backend/src/Transaction/Api/Frontend/Dto/SearchTransactionsDto.php`

Created example DTO demonstrating query parameter usage with pagination.

## Usage Examples

### Example 1: GET with Query Parameters

```php
// DTO
final readonly class GetTransactionsDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $accountId,
        
        #[Assert\Type('integer')]
        #[Assert\GreaterThan(0)]
        public int $page = 1,  // Will be coerced from string '1' to int 1
        
        #[Assert\Type('integer')]
        #[Assert\Choice(choices: [10, 20, 50])]
        public int $limit = 10,  // Will be coerced from string '10' to int 10
    ) {}
}

// Controller
#[Route('/api/transactions', methods: ['GET'])]
public function list(#[DynamicDto] GetTransactionsDto $dto): JsonResponse
{
    // $dto populated from query parameters with correct types
    return new JsonResponse([
        'page' => $dto->page,    // int, not string
        'limit' => $dto->limit,  // int, not string
    ]);
}

// Request
GET /api/transactions?accountId=550e8400-e29b-41d4-a716-446655440000&page=2&limit=20

// Result: $dto->page = 2 (int), $dto->limit = 20 (int)
```

## Type Coercion Details

Query parameters arrive as strings from the URL. The system automatically coerces them to match DTO parameter types:

### Integer Coercion
```php
// DTO
public int $page = 1;

// Query: ?page=10
// String '10' → int 10
```

### Float Coercion
```php
// DTO
public float $price;

// Query: ?price=19.99
// String '19.99' → float 19.99
```

### Boolean Coercion
```php
// DTO
public bool $active;

// Truthy values:
// ?active=true  → true
// ?active=1     → true
// ?active=yes   → true
// ?active=on    → true

// Falsy values:
// ?active=false → false
// ?active=0     → false
// ?active=no    → false
// ?active=off   → false
// ?active=      → false (empty string)
```

### Array Coercion
```php
// DTO
public array $tags;

// Query: ?tags=php,symfony,testing
// String 'php,symfony,testing' → ['php', 'symfony', 'testing']
```

### String (No Coercion)
```php
// DTO
public string $name;

// Query: ?name=John+Doe
// String 'John Doe' → 'John Doe' (unchanged)
```

### Invalid Type Conversion

If a value cannot be coerced, validation will fail:

```php
// DTO
public int $age;

// Query: ?age=not-a-number
// String 'not-a-number' kept as string → validation fails (Type constraint)
```

### Example 2: POST with JSON Body

```php
// Same DTO and controller

// Request
POST /api/transactions
Content-Type: application/json

{
  "accountId": "550e8400-e29b-41d4-a716-446655440000",
  "page": 2,
  "limit": 20
}
```

### Example 3: Mixed (Query + Body)

```php
// Request with query params and body
POST /api/transactions?page=2&limit=10
Content-Type: application/json

{
  "accountId": "550e8400-e29b-41d4-a716-446655440000",
  "limit": 20
}

// Result: accountId from body, page from query, limit from body (overrides query)
// $dto->accountId = "550e8400-e29b-41d4-a716-446655440000"
// $dto->page = 2 (from query)
// $dto->limit = 20 (from body, not 10 from query)
```

## Benefits

1. **Flexibility:** Same DTO works for GET and POST requests
2. **Consistency:** One validation logic for all request types
3. **Convenience:** Clients can mix query params and body data
4. **Type Safety:** All validation and type checking still enforced
5. **Backward Compatible:** Existing code using body-only continues to work

## Existing Controllers Using DynamicDto

These controllers now automatically support query parameters:

1. `EmployeeTransactionHistoryApiController` - can accept `page`, `limit`, `customerId`, `bankAccountId` via query
2. `AtmWithdrawalController` - can accept withdrawal data via query
3. `CustomerBlockDebitCardController` - can accept card data via query
4. All other controllers using `#[DynamicDto]`

## Migration

**No migration needed!** This is a backward-compatible enhancement. All existing code works as before, but now also supports query parameters automatically.

## Best Practices

1. **Use query params for filtering/pagination:** `?page=1&limit=10&sort=date`
2. **Use body for complex data:** Large payloads, nested objects
3. **Mix when appropriate:** Pagination in query, filters in body
4. **Document which parameters can be in query vs body** for API consumers

## Testing

To test query parameter support:

```bash
# Run unit tests
cd backend
./bin/phpunit tests/Unit/Shared/Infrastructure/Http/ArgumentResolver/DynamicDtoValueResolverTest.php

# Manual testing with curl
curl -X GET "http://localhost/api/transactions?accountId=550e8400-e29b-41d4-a716-446655440000&page=2&limit=20"

# Mixed query + body
curl -X POST "http://localhost/api/transactions?page=2" \
  -H "Content-Type: application/json" \
  -d '{"accountId":"550e8400-e29b-41d4-a716-446655440000","limit":20}'
```

## Related Files

- `backend/src/Shared/Infrastructure/Http/ArgumentResolver/DynamicDtoValueResolver.php` - Main implementation
- `backend/src/Shared/Infrastructure/Http/DynamicValidator.php` - Validation logic
- `backend/src/Shared/Infrastructure/Http/Attribute/DynamicDto.php` - Attribute marker
- `docs/DYNAMIC_DTO.md` - Comprehensive documentation
- `backend/tests/Unit/Shared/Infrastructure/Http/ArgumentResolver/DynamicDtoValueResolverTest.php` - Test coverage
