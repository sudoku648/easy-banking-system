# API Implementation Guide

This guide shows you how to implement the API endpoints that are currently marked with `@TODO`.

## General Pattern

### 1. Find the Existing Twig Controller
Look in `src/**/Presentation/Controller/` for the corresponding functionality.

### 2. Extract the Business Logic
The business logic is usually:
- Creating a Command/Query object
- Dispatching it via MessageBus
- Handling the response

### 3. Adapt to JSON
Instead of:
- Getting data from Symfony Form → Get from Request JSON
- Returning rendered Twig template → Return JSON response

### 4. Use Base Methods
The `ApiController` provides helper methods:
- `jsonSuccess($data)` - Return success response
- `jsonError($message, $status)` - Return error response

---

## Example: Implementing Transaction History

### Step 1: Find Existing Controller

Look at `src/Transaction/Presentation/Controller/CustomerViewTransactionHistoryController.php`:

```php
public function __invoke(Request $request): Response
{
    $form = $this->createForm(ViewTransactionHistoryFormType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $dto = $form->getData();
        
        $query = new GetTransactionsByAccountIdQuery(
            $dto->accountId
        );
        
        $transactions = $this->handle($query);
        
        return $this->render('transaction/history.html.twig', [
            'transactions' => $transactions,
            'form' => $form,
        ]);
    }

    return $this->render('transaction/history.html.twig', [
        'form' => $form,
    ]);
}
```

### Step 2: Implement API Version

In `CustomerApiController.php`:

```php
#[Route('/accounts/{accountId}/transactions', name: 'api_customer_transactions', methods: ['GET'])]
public function getTransactions(string $accountId): JsonResponse
{
    try {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user = $securityUser->getUser();
        
        // Verify account belongs to user
        $account = $this->handle(new GetBankAccountByIdQuery($accountId));
        
        if ($account->customerId->getValue() !== $user->id->getValue()) {
            return $this->jsonError('Access denied', 403);
        }
        
        // Get transactions
        /** @var array<Transaction> $transactions */
        $transactions = $this->handle(
            new GetTransactionsByAccountIdQuery($accountId)
        );
        
        // Map to JSON-friendly format
        $transactionsData = array_map(
            fn (Transaction $tx): array => [
                'id' => $tx->id->getValue(),
                'type' => $tx->type->value,
                'amount' => $tx->amount->getAmount(),
                'currency' => $tx->amount->getCurrency()->value,
                'description' => $tx->description,
                'status' => $tx->status->value,
                'createdAt' => $tx->createdAt->format('Y-m-d H:i:s'),
            ],
            $transactions
        );
        
        return $this->jsonSuccess(['transactions' => $transactionsData]);
        
    } catch (BankAccountNotFoundException $e) {
        return $this->jsonError('Account not found', 404);
    } catch (\Exception $e) {
        return $this->jsonError('An error occurred', 500);
    }
}
```

### Key Differences:
1. **GET parameter**: `{accountId}` from route instead of form
2. **No form handling**: Direct parameter usage
3. **Authorization**: Manual check that account belongs to user
4. **Response**: JSON instead of HTML template
5. **Error handling**: Try-catch with proper HTTP status codes

---

## Example: Implementing POST Endpoint (Transfer Money)

### Step 1: Find Existing Controller

Look at `src/Transaction/Presentation/Controller/CustomerTransferMoneyController.php`

### Step 2: Implement API Version

```php
#[Route('/transfer', name: 'api_customer_transfer', methods: ['POST'])]
public function transfer(Request $request): JsonResponse
{
    try {
        // Parse JSON request
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->jsonError('Invalid JSON', 400);
        }
        
        // Validate required fields
        $required = ['sourceAccountId', 'recipientIban', 'amount', 'title'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return $this->jsonError("Missing required field: $field", 400);
            }
        }
        
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user = $securityUser->getUser();
        
        // Create command
        $command = new TransferMoneyCommand(
            sourceAccountId: $data['sourceAccountId'],
            recipientIban: $data['recipientIban'],
            amount: (int) $data['amount'], // Already in cents from frontend
            title: $data['title'],
            customerId: $user->id->getValue(),
        );
        
        // Execute
        $this->handle($command);
        
        return $this->jsonSuccess([
            'message' => 'Transfer initiated successfully'
        ]);
        
    } catch (InsufficientFundsException $e) {
        return $this->jsonError('Insufficient funds', 400);
    } catch (BankAccountNotFoundException $e) {
        return $this->jsonError('Account not found', 404);
    } catch (ValidationException $e) {
        return $this->jsonError($e->getMessage(), 400);
    } catch (\Exception $e) {
        return $this->jsonError('Transfer failed', 500);
    }
}
```

### Key Points:
1. **Parse JSON**: `json_decode($request->getContent())`
2. **Validate**: Check required fields manually
3. **Create Command**: Same as Twig version
4. **Handle Exceptions**: Map domain exceptions to HTTP status codes
5. **Return JSON**: Success or error response

---

## Common Patterns

### Getting Current User
```php
/** @var SecurityUser $securityUser */
$securityUser = $this->getUser();
$user = $securityUser->getUser();
```

### Parsing JSON Request
```php
$data = json_decode($request->getContent(), true);
if (!$data) {
    return $this->jsonError('Invalid JSON', 400);
}
```

### Validating Required Fields
```php
$required = ['field1', 'field2'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        return $this->jsonError("Missing field: $field", 400);
    }
}
```

### Using MessageBus
```php
use Symfony\Component\Messenger\HandleTrait;

// In controller class
use HandleTrait;

public function __construct(MessageBusInterface $messageBus)
{
    $this->messageBus = $messageBus;
}

// In method
$result = $this->handle($command); // or $query
```

### Mapping Entities to JSON
```php
$data = array_map(
    fn (Entity $entity): array => [
        'id' => $entity->id->getValue(),
        'name' => $entity->name->getValue(),
        // ... other fields
    ],
    $entities
);
```

### Error Handling
```php
try {
    // Business logic
    return $this->jsonSuccess($data);
} catch (DomainException $e) {
    return $this->jsonError($e->getMessage(), 400);
} catch (NotFoundException $e) {
    return $this->jsonError('Not found', 404);
} catch (\Exception $e) {
    // Log the error
    return $this->jsonError('Internal error', 500);
}
```

---

## HTTP Status Codes

Use appropriate status codes:
- **200 OK**: Success with data
- **201 Created**: Resource created successfully
- **204 No Content**: Success with no data
- **400 Bad Request**: Validation error, invalid input
- **401 Unauthorized**: Not authenticated
- **403 Forbidden**: Authenticated but not authorized
- **404 Not Found**: Resource doesn't exist
- **409 Conflict**: Business rule violation
- **500 Internal Server Error**: Unexpected error

---

## Testing the API

### Using Browser Console
```javascript
// GET request
fetch('/api/customer/accounts')
  .then(r => r.json())
  .then(console.log)

// POST request
fetch('/api/customer/transfer', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    sourceAccountId: 'uuid-here',
    recipientIban: 'PL...',
    amount: 10000, // cents
    title: 'Test transfer'
  })
})
  .then(r => r.json())
  .then(console.log)
```

### Using curl
```bash
# GET
curl http://localhost:8080/api/customer/accounts

# POST
curl -X POST http://localhost:8080/api/customer/transfer \
  -H 'Content-Type: application/json' \
  -d '{"sourceAccountId":"uuid","recipientIban":"PL...","amount":10000,"title":"Test"}'
```

---

## Checklist for Each Endpoint

- [ ] Find corresponding Twig controller
- [ ] Copy business logic (Command/Query creation)
- [ ] Add JSON request parsing (for POST)
- [ ] Add input validation
- [ ] Add authorization checks
- [ ] Map result to JSON-friendly format
- [ ] Add error handling with proper HTTP codes
- [ ] Remove @TODO comment
- [ ] Test with browser/curl
- [ ] Update presentation tests

---

## Need Help?

1. Look at `CustomerApiController::getAccounts()` - it's already implemented as an example
2. Check existing Twig controllers for business logic
3. Review domain layer for available Commands/Queries
4. Check tests for expected behavior

---

## Tips

1. **Start simple**: Implement GET endpoints first (easier)
2. **Test frequently**: Check each endpoint as you implement it
3. **Reuse code**: Business logic is the same, only interface changes
4. **Follow conventions**: Keep same patterns across all endpoints
5. **Error messages**: Make them helpful for frontend developers
