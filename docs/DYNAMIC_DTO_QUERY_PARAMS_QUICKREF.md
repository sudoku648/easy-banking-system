# DynamicDto Query Parameters - Quick Reference

## TL;DR

DynamicDto now supports query parameters automatically with **automatic type coercion**! Query string values are converted to match DTO types.

## Type Coercion Examples

```php
// DTO
final readonly class SearchDto
{
    public function __construct(
        public string $query,   // No coercion
        public int $page,       // '10' → 10
        public bool $active,    // 'true' → true
        public float $price,    // '19.99' → 19.99
        public array $tags,     // 'a,b,c' → ['a','b','c']
    ) {}
}
```

### Quick Type Reference

| Type | Example Query | Coerced To |
|------|---------------|------------|
| `int` | `?page=10` | `10` (integer) |
| `float` | `?price=19.99` | `19.99` (float) |
| `bool` | `?active=true` | `true` (boolean) |
| `bool` | `?active=1` | `true` (boolean) |
| `bool` | `?active=false` | `false` (boolean) |
| `array` | `?tags=a,b,c` | `['a','b','c']` |
| `string` | `?name=John` | `'John'` (unchanged) |

## Basic Usage

```php
// DTO with pagination
final readonly class ListDto
{
    public function __construct(
        #[Assert\Type('integer')]
        public int $page = 1,
        
        #[Assert\Type('integer')]
        public int $limit = 10,
    ) {}
}

// Controller
public function list(#[DynamicDto] ListDto $dto): JsonResponse
{
    // Works with both query params and body!
}
```

## Request Examples

### 1. Query Parameters (GET)
```bash
GET /api/items?page=2&limit=20
```

### 2. Request Body (POST)
```bash
POST /api/items
Content-Type: application/json

{"page": 2, "limit": 20}
```

### 3. Mixed (Query + Body)
```bash
POST /api/items?page=2
Content-Type: application/json

{"limit": 20}
```

**Result:** `page=2` from query, `limit=20` from body

## Precedence Rules

When same parameter exists in both:

```bash
GET /api/items?limit=10
Body: {"limit": 20}
```

**Body wins:** `$dto->limit` will be `20`

## Common Patterns

### Pattern 1: Pagination in Query
```php
GET /api/transactions?page=2&limit=50
```

### Pattern 2: Filters in Body, Pagination in Query
```php
POST /api/transactions?page=2&limit=20
Content-Type: application/json

{
  "accountId": "550e8400-e29b-41d4-a716-446655440000",
  "dateFrom": "2024-01-01",
  "dateTo": "2024-12-31"
}
```

### Pattern 3: All in Query (Simple GET)
```php
GET /api/transactions?accountId=550e8400-e29b-41d4-a716-446655440000&page=1&limit=10
```

## Validation

All validation rules apply to merged data:

```php
// DTO
public int $limit = 10,  // Default if not provided

// These all validate the same:
GET /api/items?limit=999     // ❌ Fails validation
POST /api/items {"limit": 999} // ❌ Fails validation
```

## Migration Checklist

✅ No changes needed!
✅ Existing code works as before
✅ Query params now work automatically
✅ Update API docs to mention query param support

## See Also

- [DYNAMIC_DTO.md](DYNAMIC_DTO.md) - Full documentation
- [DYNAMIC_DTO_QUERY_PARAMS.md](DYNAMIC_DTO_QUERY_PARAMS.md) - Detailed implementation guide
