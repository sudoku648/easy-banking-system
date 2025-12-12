# Type Coercion Cheat Sheet

## Quick Reference

When using query parameters with DynamicDto, strings are automatically converted:

```
?page=10        → $dto->page = 10 (int)
?price=19.99    → $dto->price = 19.99 (float)
?active=true    → $dto->active = true (bool)
?tags=a,b,c     → $dto->tags = ['a','b','c'] (array)
?name=John      → $dto->name = 'John' (string)
```

## Boolean Values

✅ **True:** `true`, `1`, `yes`, `on`
✅ **False:** `false`, `0`, `no`, `off`, `` (empty)

## Common Patterns

### Pagination
```php
public int $page = 1;
public int $limit = 10;

// Query: ?page=2&limit=20
// Works! ✅
```

### Filtering
```php
public bool $active = true;
public float $minPrice = 0.0;

// Query: ?active=false&minPrice=10.50
// Works! ✅
```

### Search
```php
public string $query;
public array $categories = [];

// Query: ?query=test&categories=books,tech
// Works! ✅
```

## What Gets Coerced?

✅ Query parameters (always strings)
✅ Form data (may be strings)
❌ JSON body (already typed correctly)

## Error Handling

Invalid coercion → Validation fails:

```php
public int $age;

// Query: ?age=abc
// Result: Validation error (Type constraint)
```

## Tips

1. **Always add type hints:** `public int $page` (not `public $page`)
2. **Use validation constraints:** `#[Assert\Type('integer')]`
3. **Test with query params:** URLs are easy to test in browser
4. **Check actual types in tests:** `assertIsInt()`, `assertIsBool()`

## Example DTO

```php
final readonly class SearchDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $query,           // No coercion needed
        
        #[Assert\Type('integer')]
        #[Assert\GreaterThan(0)]
        public int $page = 1,          // '1' → 1
        
        #[Assert\Type('integer')]
        #[Assert\Choice([10, 20, 50])]
        public int $limit = 10,        // '10' → 10
        
        #[Assert\Type('boolean')]
        public bool $includeInactive = false,  // 'false' → false
    ) {}
}
```

## Testing URLs

```bash
# All integers coerced
GET /api/search?query=test&page=2&limit=20&includeInactive=true

# With invalid type (fails validation)
GET /api/search?query=test&page=abc
# → {"errors": [{"path": "page", "message": "This value should be of type integer."}]}
```

## See Also

- [TYPE_COERCION_IMPLEMENTATION.md](TYPE_COERCION_IMPLEMENTATION.md) - Full details
- [DYNAMIC_DTO.md](DYNAMIC_DTO.md) - Complete guide
- [DYNAMIC_DTO_QUERY_PARAMS_QUICKREF.md](DYNAMIC_DTO_QUERY_PARAMS_QUICKREF.md) - Query param basics
