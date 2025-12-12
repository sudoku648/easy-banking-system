# Query Parameters Usage Examples

## EmployeeTransactionHistoryApiController

The `EmployeeTransactionHistoryApiController` now supports query parameters in addition to POST body.

### Original DTO
```php
final class EmploeeTransactionHistoryDto
{
    public function __construct(
        #[Assert\Type(type: 'string')]
        #[Assert\Uuid]
        public ?string $customerId = null,
        
        #[Assert\Type(type: 'string')]
        #[Assert\Uuid]
        public ?string $bankAccountId = null,
        
        #[Assert\Type(type: 'integer')]
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $page = 1,
        
        #[Assert\Type(type: 'integer')]
        #[Assert\NotBlank]
        #[Assert\Choice(choices: [10, 20, 50])]
        public int $limit = 10,
    ) {}
}
```

### Usage Examples

#### Example 1: GET with Query Parameters (NEW!)
```bash
# View customer's all accounts history
GET /api/frontend/employee/transaction/history/api?customerId=550e8400-e29b-41d4-a716-446655440000&page=2&limit=20

# View specific bank account history
GET /api/frontend/employee/transaction/history/api?bankAccountId=660e8400-e29b-41d4-a716-446655440000&page=1&limit=10
```

#### Example 2: POST with JSON Body (Original, still works)
```bash
POST /api/frontend/employee/transaction/history/api
Content-Type: application/json

{
  "customerId": "550e8400-e29b-41d4-a716-446655440000",
  "page": 2,
  "limit": 20
}
```

#### Example 3: Mixed Query + Body (NEW!)
```bash
# Pagination in query, filters in body
POST /api/frontend/employee/transaction/history/api?page=2&limit=50
Content-Type: application/json

{
  "bankAccountId": "660e8400-e29b-41d4-a716-446655440000"
}
```

### Benefits for This Controller

1. **RESTful GET support:** Can now use proper GET requests for reading data
2. **Browser-friendly:** Can be tested directly in browser address bar
3. **Bookmarkable:** Users can bookmark URLs with parameters
4. **Link-friendly:** Can share direct links to specific pages/filters

### Before vs After

**Before (POST only):**
```javascript
// JavaScript example
fetch('/api/frontend/employee/transaction/history/api', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    customerId: '550e8400-e29b-41d4-a716-446655440000',
    page: 2,
    limit: 20
  })
});
```

**After (GET with query params):**
```javascript
// Much simpler!
fetch('/api/frontend/employee/transaction/history/api?customerId=550e8400-e29b-41d4-a716-446655440000&page=2&limit=20');
```

## Other Controllers Now Supporting Query Parameters

All controllers using `#[DynamicDto]` now support query parameters:

### 1. AtmWithdrawalController
```bash
# Can now use query params for ATM withdrawals
POST /api/external/atm/withdrawal?amount=100&currency=USD
Body: {"accountId": "..."}
```

### 2. CustomerBlockDebitCardController
```bash
# Can use query params for card blocking
POST /api/frontend/customer/debit-card/block?cardId=123
```

### 3. CustomerChangePasswordController
```bash
# Password change with mixed params
POST /api/frontend/customer/password?userId=550e8400-e29b-41d4-a716-446655440000
Body: {"oldPassword": "...", "newPassword": "..."}
```

## API Design Recommendations

### Use Query Params For:
- ✅ Pagination (`page`, `limit`, `offset`)
- ✅ Sorting (`sort`, `order`)
- ✅ Simple filters (`status`, `type`)
- ✅ Search queries (`q`, `search`)
- ✅ IDs and identifiers

### Use Body For:
- ✅ Complex filters (nested objects)
- ✅ Large data payloads
- ✅ Sensitive data (passwords, tokens)
- ✅ Multi-level structures

### Example: Best Practices
```bash
# Good: Simple params in query, complex in body
POST /api/search?page=2&limit=20&sort=date
Body: {
  "filters": {
    "dateRange": {"from": "2024-01-01", "to": "2024-12-31"},
    "amounts": {"min": 100, "max": 1000},
    "tags": ["important", "urgent"]
  }
}

# Also Good: Everything in query for simple cases
GET /api/items?page=1&limit=10&status=active

# Also Good: Everything in body for complex cases
POST /api/search
Body: {
  "page": 2,
  "limit": 20,
  "filters": {...}
}
```

## Testing

### cURL Examples

```bash
# Test GET with query params
curl -X GET "http://localhost/api/frontend/employee/transaction/history/api?customerId=550e8400-e29b-41d4-a716-446655440000&page=2&limit=20" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Test POST with query params
curl -X POST "http://localhost/api/frontend/employee/transaction/history/api?page=2" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"customerId":"550e8400-e29b-41d4-a716-446655440000","limit":50}'

# Test with invalid params (should fail validation)
curl -X GET "http://localhost/api/frontend/employee/transaction/history/api?customerId=invalid-uuid&page=0" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Expected Responses

**Valid Request:**
```json
{
  "transactions": [...],
  "total": 150,
  "page": 2,
  "limit": 20,
  "totalPages": 8,
  "customerName": "John Doe",
  "accountIban": null
}
```

**Invalid Request (validation error):**
```json
{
  "errors": [
    {
      "path": "customerId",
      "message": "This is not a valid UUID."
    },
    {
      "path": "page",
      "message": "This value should be greater than 0."
    }
  ]
}
```

## Frontend Integration

### React Example

```typescript
// Before: Had to use POST
const fetchTransactions = async (customerId: string, page: number) => {
  const response = await fetch('/api/frontend/employee/transaction/history/api', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ customerId, page, limit: 20 })
  });
  return response.json();
};

// After: Can use GET (more semantic)
const fetchTransactions = async (customerId: string, page: number) => {
  const params = new URLSearchParams({
    customerId,
    page: page.toString(),
    limit: '20'
  });
  const response = await fetch(`/api/frontend/employee/transaction/history/api?${params}`);
  return response.json();
};
```

### Benefits in Frontend

1. **Better caching:** Browsers cache GET requests by default
2. **Simpler code:** No need to serialize body
3. **Better DevTools:** Query params visible in Network tab
4. **Share/Bookmark:** URLs can be copied and shared

## Migration Notes

- **No backend changes needed:** All existing POST endpoints continue to work
- **Frontend can migrate gradually:** Start using query params in new code
- **Both methods work:** POST with body and GET with query params both supported
- **Validation unchanged:** Same validation rules apply regardless of source
