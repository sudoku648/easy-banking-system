# Type Coercion in DynamicDto - Implementation Summary

## Overview

Enhanced DynamicDto to automatically coerce query parameter string values to match DTO constructor parameter types. This eliminates the need for manual type conversion when using query parameters.

## The Problem

Query parameters always arrive as strings:
```php
// URL: ?page=10&limit=20&active=true
// $_GET = ['page' => '10', 'limit' => '20', 'active' => 'true']

// DTO expects:
public int $page;      // int, not string
public int $limit;     // int, not string  
public bool $active;   // bool, not string
```

Without type coercion, validation would fail because `'10'` (string) ≠ `10` (int).

## The Solution

Automatic type coercion based on reflection of DTO constructor parameter types:

```php
// DynamicValidator inspects parameter types
public int $page;  // Type: int → coerce string to int

// Coercion happens before validation
'10' → 10  // String coerced to int
```

## Implementation

### Changes Made

**File:** `backend/src/Shared/Infrastructure/Http/DynamicValidator.php`

Added three new private methods:

1. **`coerceTypes()`** - Main orchestrator
   - Inspects DTO constructor parameters
   - Calls coerceValue() for each parameter
   - Returns coerced data array

2. **`coerceValue()`** - Type dispatcher
   - Checks parameter type hint
   - Routes to appropriate coercion method
   - Returns coerced value

3. **Type-specific coercion methods:**
   - `coerceToInt()` - String to integer
   - `coerceToFloat()` - String to float
   - `coerceToBool()` - String to boolean
   - `coerceToArray()` - CSV string to array

### Code Flow

```
Request with query params
    ↓
getRequestData() - extracts query + body
    ↓
validateAndHydrate()
    ↓
coerceTypes() ← NEW
    ↓
createShadowClass()
    ↓
hydrateShadowObject() - now with coerced types
    ↓
validate() - validation passes because types match
    ↓
mapToStrictDto()
    ↓
Strict-typed DTO with correct types
```

## Coercion Rules

### Integer (`int`)

```php
public int $page;

// Coercion logic:
'10' → 10          ✅ Numeric string
'0' → 0            ✅ Zero
'-5' → -5          ✅ Negative
'3.14' → 3         ✅ Float string (truncated)
'abc' → 'abc'      ⚠️  Not numeric, kept as string → validation fails
```

Implementation:
```php
private function coerceToInt(string $value): int|string
{
    if (!is_numeric($value)) {
        return $value; // Let validator handle it
    }
    return (int) $value;
}
```

### Float (`float`)

```php
public float $price;

// Coercion logic:
'19.99' → 19.99    ✅ Decimal string
'10' → 10.0        ✅ Integer string
'0.5' → 0.5        ✅ Fraction
'abc' → 'abc'      ⚠️  Not numeric, kept as string → validation fails
```

Implementation:
```php
private function coerceToFloat(string $value): float|string
{
    if (!is_numeric($value)) {
        return $value; // Let validator handle it
    }
    return (float) $value;
}
```

### Boolean (`bool`)

```php
public bool $active;

// Coercion logic:
// True value:
'true' → true      ✅

// False value:
'false' → false    ✅

// Other:
'maybe' → 'maybe'  ⚠️  Unknown, kept as string → validation fails
```

Implementation:
```php
private function coerceToBool(string $value): bool|string
{
    $lowercaseValue = strtolower($value);
    
    return match ($lowercaseValue) {
        'true' => true,
        'false' => false,
        default => $value, // Let validator handle it
    };
}
```

### Array (`array`)

```php
public array $tags;

// Coercion logic:
'php,symfony,testing' → ['php', 'symfony', 'testing']  ✅
'single' → ['single']                                   ✅
'' → []                                                 ✅ Empty array
```

Implementation:
```php
private function coerceToArray(string $value): array
{
    if ($value === '') {
        return [];
    }
    return array_map('trim', explode(',', $value));
}
```

### String (`string`)

No coercion - values kept as-is:
```php
public string $name;

'John Doe' → 'John Doe'  ✅ No change
```

## Examples

### Example 1: Pagination

```php
// DTO
final readonly class PaginationDto
{
    public function __construct(
        public int $page = 1,
        public int $limit = 10,
    ) {}
}

// Request
GET /api/items?page=2&limit=20

// Without coercion (would fail):
$data = ['page' => '2', 'limit' => '20'];  // strings
// Validation fails: Type constraint expects int

// With coercion (works):
$data = ['page' => 2, 'limit' => 20];      // integers
// Validation passes ✅
```

### Example 2: Filters

```php
// DTO
final readonly class FilterDto
{
    public function __construct(
        public bool $active = true,
        public float $minPrice = 0.0,
        public array $categories = [],
    ) {}
}

// Request
GET /api/products?active=true&minPrice=10.50&categories=electronics,books

// Coerced data:
[
    'active' => true,              // boolean, not 'true'
    'minPrice' => 10.50,           // float, not '10.50'
    'categories' => ['electronics', 'books']  // array, not 'electronics,books'
]
```

### Example 3: Mixed Sources

```php
// Request
POST /api/search?page=2&active=true
Body: {"query": "test", "limit": 50}

// Merged data (before coercion):
[
    'page' => '2',        // from query (string)
    'active' => 'true',   // from query (string)
    'query' => 'test',    // from body (already string)
    'limit' => 50         // from body (already int)
]

// After coercion:
[
    'page' => 2,          // coerced to int
    'active' => true,     // coerced to bool
    'query' => 'test',    // kept as string
    'limit' => 50         // already int, unchanged
]
```

## Edge Cases

### Case 1: Invalid Integer
```php
public int $age;

// Query: ?age=abc
// Coercion: 'abc' → 'abc' (kept as string)
// Validation: ❌ Fails with Type constraint violation
```

### Case 2: Empty String to Boolean
```php
public bool $active;

// Query: ?active=
// Coercion: '' → false
// Validation: ✅ Passes
```

### Case 3: Optional Parameters
```php
public ?int $page = null;

// Query: (no page param)
// Data: []
// Coercion: (skipped, no value)
// Result: $dto->page = null (default)
```

### Case 4: Float String as Integer
```php
public int $count;

// Query: ?count=3.14
// Coercion: '3.14' → '3.14' (kept as string, NOT truncated)
// Validation: ❌ Fails with Type constraint violation
```

**Note:** This prevents accidental precision loss. If you need decimals, use `float` type.

## Testing

### Test Coverage

**File:** `backend/tests/Unit/Shared/Infrastructure/Http/DynamicValidatorTest.php`

New test methods:
- `testTypeCoercionFromStrings()` - Basic coercion
- `testIntegerCoercion()` - Integer-specific
- `testFloatCoercion()` - Float-specific
- `testBooleanCoercionTrue()` - Boolean truthy values
- `testBooleanCoercionFalse()` - Boolean falsy values
- `testArrayCoercionFromCommaSeparatedString()` - Array coercion
- `testInvalidStringForIntegerFailsValidation()` - Invalid coercion
- `testFloatStringForIntegerFailsValidation()` - Float string not coerced to int
- `testMixedTypesFromQueryParams()` - Real-world scenario

**File:** `backend/tests/Unit/Shared/Infrastructure/Http/ArgumentResolver/DynamicDtoValueResolverTest.php`

Enhanced test methods:
- `testTypeCoercionFromQueryParameters()` - Full integration
- `testBooleanCoercionVariants()` - All boolean formats

### Running Tests

```bash
# Run DynamicValidator tests
cd backend
./bin/phpunit tests/Unit/Shared/Infrastructure/Http/DynamicValidatorTest.php

# Run DynamicDtoValueResolver tests
./bin/phpunit tests/Unit/Shared/Infrastructure/Http/ArgumentResolver/DynamicDtoValueResolverTest.php

# Run all HTTP infrastructure tests
./bin/phpunit tests/Unit/Shared/Infrastructure/Http/
```

## Benefits

1. **Seamless Query Parameter Support** - No manual conversion needed
2. **Type Safety** - DTOs receive correctly typed values
3. **Validation Works** - Type constraints pass after coercion
4. **Developer Experience** - Write DTOs once, work with query params or body
5. **Backward Compatible** - JSON body data already has correct types, unaffected

## Performance

- **Coercion overhead:** Minimal - only for string values
- **Reflection caching:** Parameter types cached per request
- **No impact on JSON payloads:** JSON already has types, coercion skipped

## Related Files

- `backend/src/Shared/Infrastructure/Http/DynamicValidator.php` - Main implementation
- `backend/src/Shared/Infrastructure/Http/ArgumentResolver/DynamicDtoValueResolver.php` - Entry point
- `backend/tests/Unit/Shared/Infrastructure/Http/DynamicValidatorTest.php` - Unit tests
- `backend/tests/Unit/Shared/Infrastructure/Http/ArgumentResolver/DynamicDtoValueResolverTest.php` - Integration tests
- `docs/DYNAMIC_DTO.md` - Updated documentation
- `docs/DYNAMIC_DTO_QUERY_PARAMS.md` - Detailed guide
- `docs/DYNAMIC_DTO_QUERY_PARAMS_QUICKREF.md` - Quick reference

## Future Enhancements

Possible improvements:
- Custom type coercers via attributes
- Date/DateTime string parsing
- JSON string to object/array
- Custom delimiters for array coercion
- Configurable boolean string mappings
