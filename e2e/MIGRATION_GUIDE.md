# E2E Test Migration Checklist

## For Developers: Moving from PHPUnit to Playwright

This checklist helps you understand what changed and how to work with the new e2e tests.

## ✅ What Changed

### Before (PHPUnit Presentation Tests)
- **Location**: `tests/Presentation/`
- **Framework**: PHPUnit with Symfony test client
- **Target**: Twig-based server-rendered pages
- **Browser**: Single (simulated via test client)
- **Command**: `make test --testsuite=presentation`

### After (Playwright E2E Tests)
- **Location**: `e2e/`
- **Framework**: Playwright Test
- **Target**: React SPA frontend
- **Browsers**: Chromium, Firefox, WebKit (real browsers)
- **Command**: `make e2e`

## 📋 Migration Mapping

### Test File Mapping

| PHPUnit Test | Playwright Test | Status |
|--------------|-----------------|--------|
| `tests/Presentation/UserManagement/Controller/SecurityControllerTest.php` | `e2e/auth.spec.js` | ✅ |
| `tests/Presentation/UserManagement/Controller/CustomerDashboardControllerTest.php` | `e2e/customer-dashboard.spec.js` | ✅ |
| `tests/Presentation/Transaction/Controller/TransactionControllerTest.php` | `e2e/customer-transactions.spec.js` | ✅ |
| `tests/Presentation/UserManagement/Controller/EmployeeDashboardControllerTest.php` | `e2e/employee-operations.spec.js` | ✅ |
| `tests/Presentation/Transaction/Controller/EmployeeTransactionControllerTest.php` | `e2e/employee-operations.spec.js` | ✅ |
| `tests/Presentation/BankAccount/Controller/BankAccountControllerTest.php` | `e2e/bank-account-management.spec.js` | ✅ |

### Test Pattern Conversion

#### 1. Accessing Pages

**PHPUnit:**
```php
$this->client->request('GET', '/customer/dashboard');
```

**Playwright:**
```javascript
await page.goto('/en/customer/dashboard');
```

#### 2. Filling Forms

**PHPUnit:**
```php
$form = $crawler->selectButton('Submit')->form([
    'username' => 'testuser',
    'password' => 'password'
]);
$this->client->submit($form);
```

**Playwright:**
```javascript
await page.fill('input[name="username"]', 'testuser');
await page.fill('input[name="password"]', 'password');
await page.click('button[type="submit"]');
```

#### 3. Assertions

**PHPUnit:**
```php
$this->assertResponseIsSuccessful();
$this->assertPageContains('Welcome');
```

**Playwright:**
```javascript
await expect(page).toHaveURL(/\/dashboard/);
await expect(page.getByText('Welcome')).toBeVisible();
```

#### 4. Authentication

**PHPUnit:**
```php
$customer = $this->createCustomer('user', 'pass');
$this->loginAsCustomerUser($customer);
```

**Playwright:**
```javascript
await page.goto('/en/login');
await page.fill('input[name="username"]', 'testcustomer');
await page.fill('input[name="password"]', 'password123');
await page.click('button[type="submit"]');
```

#### 5. Flash Messages

**PHPUnit:**
```php
$this->assertHasFlashMessage('success', 'Operation successful');
```

**Playwright:**
```javascript
await expect(page.locator('.alert-success')).toBeVisible();
await expect(page.locator('.alert-success')).toContainText(/Operation successful/);
```

## 🔄 Workflow Changes

### Running Tests

#### Before (PHPUnit)
```bash
make test --testsuite=presentation
```

#### After (Playwright)
```bash
# All tests
make e2e

# With UI (recommended)
make e2e-ui

# Debug mode
make e2e-debug

# Specific file
npx playwright test e2e/auth.spec.js

# Specific test
npx playwright test -g "login as customer"
```

### Debugging Tests

#### Before (PHPUnit)
```bash
# Limited debugging with var_dump, dd()
make test --testsuite=presentation
```

#### After (Playwright)
```bash
# Interactive debugging
make e2e-ui

# Step-by-step debugging
make e2e-debug

# View trace
npx playwright show-trace test-results/[test]/trace.zip

# Generate tests
npx playwright codegen http://localhost:3000
```

### Test Data Setup

#### Before (PHPUnit)
```php
protected function setUp(): void {
    parent::setUp();
    $this->createCustomer('user', 'pass');
    $this->createAccount($customer, 'PLN');
}
```

#### After (Playwright)
```bash
# Load fixtures once
make fixtures

# Or in code (if API available)
await apiClient.createCustomer({...});
await apiClient.createAccount({...});
```

## 📝 Code Examples

### Example 1: Login Test

**PHPUnit:**
```php
public function testCustomerCanLogin(): void
{
    $customer = $this->createCustomer('customer1', 'pass123');
    $crawler = $this->loginAsCustomer($customer, 'pass123');
    
    $this->assertOnRoute('customer_dashboard');
    $this->assertPageContains('Customer Dashboard');
}
```

**Playwright:**
```javascript
test('should login as customer and redirect to dashboard', async ({ page }) => {
  await page.goto('/en/login');
  await page.fill('input[name="username"]', 'testcustomer');
  await page.fill('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');
  
  await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
  await expect(page.getByText('Customer Dashboard')).toBeVisible();
});
```

### Example 2: Form Validation

**PHPUnit:**
```php
public function testTransferWithInvalidAmountShowsError(): void
{
    $customer = $this->createCustomer('customer1', 'pass123');
    $this->loginAsCustomerUser($customer);
    
    $crawler = $this->client->request('GET', '/customer/transaction/transfer');
    $form = $crawler->selectButton('Transfer')->form([
        'transfer_money_form[amount]' => '0',
    ]);
    
    $this->client->submit($form);
    $this->assertResponseIsUnprocessable();
    $this->assertPageContains('This value should be positive');
}
```

**Playwright:**
```javascript
test('should show error with zero amount', async ({ page }) => {
  await page.goto('/en/login');
  await page.fill('input[name="username"]', 'testcustomer');
  await page.fill('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');
  
  await page.goto('/en/customer/transfer-money');
  await page.fill('input[name="amount"]', '0');
  await page.click('button[type="submit"]');
  
  await expect(page.locator('.alert-danger, .invalid-feedback')).toBeVisible();
  await expect(page.getByText(/positive/i)).toBeVisible();
});
```

## 🎯 Best Practices

### 1. Use Semantic Selectors

**Prefer:**
```javascript
await page.getByRole('button', { name: 'Submit' })
await page.getByLabel('Username')
await page.getByText('Welcome')
```

**Over:**
```javascript
await page.click('.btn-primary')
await page.fill('#username-field')
```

### 2. Wait for Conditions

**Good:**
```javascript
await expect(page.locator('.loading')).not.toBeVisible();
await expect(page.getByText('Loaded')).toBeVisible();
```

**Bad:**
```javascript
await page.waitForTimeout(3000); // Avoid hard waits
```

### 3. Use Helper Functions

Create reusable helpers in `e2e/utils/`:
```javascript
// Login helper
export async function loginAsCustomer(page) {
  await page.goto('/en/login');
  await page.fill('input[name="username"]', 'testcustomer');
  await page.fill('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForURL(/\/customer\/dashboard/);
}
```

### 4. Organize Tests

```javascript
test.describe('Feature Name', () => {
  test.describe('Sub-feature', () => {
    test('specific behavior', async ({ page }) => {
      // test code
    });
  });
});
```

## 🚀 Getting Started

### For New Team Members

1. **Clone repository**
2. **Install dependencies**: `npm install`
3. **Install browsers**: `make e2e-install`
4. **Read documentation**: `e2e/README.md`
5. **Run first test**: `make e2e-ui`

### For Existing Team Members

1. **Update dependencies**: `npm install`
2. **Install Playwright**: `make e2e-install`
3. **Review changes**: Read this checklist
4. **Try UI mode**: `make e2e-ui`
5. **Explore tests**: Look at `e2e/*.spec.js`

## 📚 Resources

- **Main Guide**: `e2e/README.md`
- **Setup Guide**: `e2e/SETUP.md`
- **Quick Reference**: `e2e/QUICK_REFERENCE.md`
- **Playwright Docs**: https://playwright.dev/

## ❓ FAQ

### Q: Do I need to keep PHPUnit Presentation tests?
**A:** No, the Playwright e2e tests replace them for frontend testing. PHPUnit tests for API, unit, and integration should remain.

### Q: Can I run both test suites?
**A:** Yes, but they test different things now:
- PHPUnit: API, business logic, integration
- Playwright: Frontend user flows

### Q: Which tests should I write where?
**A:** 
- User flows, UI interactions → Playwright (e2e)
- API endpoints, services → PHPUnit (tests/)
- Business logic → PHPUnit (tests/Unit)
- Integration → PHPUnit (tests/Integration)

### Q: How do I debug a failing test?
**A:**
1. Run with UI mode: `make e2e-ui`
2. Or debug mode: `make e2e-debug`
3. Check screenshots in `test-results/`
4. View trace: `npx playwright show-trace test-results/[test]/trace.zip`

### Q: Tests are flaky, what should I do?
**A:**
- Add proper waits: `await expect(element).toBeVisible()`
- Wait for network: `await page.waitForLoadState('networkidle')`
- Avoid `waitForTimeout()`
- Use stable selectors

### Q: How do I add a new test?
**A:**
1. Choose appropriate spec file (or create new one)
2. Follow existing patterns
3. Use helper functions from `e2e/utils/`
4. Test locally with `make e2e-ui`
5. Run full suite before committing

## ✅ Checklist for PR Reviews

When reviewing PRs with e2e test changes:

- [ ] Tests follow existing patterns
- [ ] Proper waits used (no hard timeouts)
- [ ] Semantic selectors used where possible
- [ ] Tests are independent (no order dependency)
- [ ] Test names are descriptive
- [ ] Assertions are meaningful
- [ ] Tests pass in CI
- [ ] Documentation updated if needed

---

**Migration completed**: December 4, 2025  
**Questions?** See `e2e/README.md` or contact the team
