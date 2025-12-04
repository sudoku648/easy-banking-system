# E2E Testing Guide

This guide explains how to run and write end-to-end (e2e) tests for the Easy Banking System React frontend using Playwright.

## Overview

The e2e tests are based on the existing Presentation tests (PHPUnit) that tested the Twig-based frontend. They have been migrated to test the new React SPA frontend while maintaining the same test coverage and scenarios.

## Prerequisites

- Node.js and npm installed
- Docker and Docker Compose (for backend services)
- Frontend and backend services running

## Installation

### 1. Install Dependencies

```bash
# Install frontend dependencies including Playwright
make frontend-install
# or
npm install
```

### 2. Install Playwright Browsers

```bash
# Install Playwright browsers and system dependencies
make e2e-install
# or
npx playwright install --with-deps
```

## Running Tests

### Run All Tests

```bash
# Run all e2e tests
make e2e
# or
npm run test:e2e
```

### Run Tests in UI Mode

```bash
# Run tests with Playwright UI (interactive mode)
make e2e-ui
# or
npm run test:e2e:ui
```

### Run Tests in Headed Mode

```bash
# Run tests with visible browser
make e2e-headed
# or
npm run test:e2e:headed
```

### Debug Mode

```bash
# Run tests in debug mode with Playwright Inspector
make e2e-debug
# or
npm run test:e2e:debug
```

### View Test Report

```bash
# Open the HTML test report
make e2e-report
# or
npm run test:e2e:report
```

## Test Structure

```
e2e/
├── auth.spec.js                      # Authentication and login tests
├── customer-dashboard.spec.js        # Customer dashboard tests
├── customer-transactions.spec.js     # Transfer money and transaction history
├── employee-operations.spec.js       # Employee dashboard and operations
├── bank-account-management.spec.js   # Account opening and closing
├── fixtures.js                       # Test fixtures and helpers
├── utils/
│   ├── api-client.js                # API client for test setup
│   └── helpers.js                   # Helper functions
└── screenshots/                     # Screenshots on failure
```

## Test Files Overview

### auth.spec.js
Tests authentication flows:
- Login page rendering
- Successful login (customer/employee)
- Invalid credentials
- Logout functionality
- Route protection
- Home page redirects

### customer-dashboard.spec.js
Tests customer dashboard:
- Access control
- Bank account display
- Multiple accounts
- Account status (active/closed)
- Navigation links
- Quick actions
- Responsive design

### customer-transactions.spec.js
Tests transaction features:
- Transfer money form
- Transfer validation
- Successful transfers
- Transaction history display
- Insufficient balance errors

### employee-operations.spec.js
Tests employee features:
- Employee dashboard
- Deposit money
- Customer selection
- Transaction history viewing

### bank-account-management.spec.js
Tests account management:
- Open account for new customer
- Open account for existing customer
- Close account
- Form validation
- Error handling

## Writing Tests

### Basic Test Structure

```javascript
import { test, expect } from '@playwright/test';

test.describe('Feature Name', () => {
  
  test('should do something', async ({ page }) => {
    // Navigate to page
    await page.goto('/en/login');
    
    // Interact with elements
    await page.fill('input[name="username"]', 'testuser');
    await page.click('button[type="submit"]');
    
    // Assert expectations
    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.getByText('Welcome')).toBeVisible();
  });
});
```

### Common Patterns

#### Login Helper

```javascript
// Login as customer
await page.goto('/en/login');
await page.fill('input[name="username"]', 'testcustomer');
await page.fill('input[name="password"]', 'TestPass123!');
await page.click('button[type="submit"]');
await expect(page).toHaveURL(/\/customer\/dashboard/);
```

#### Checking Flash Messages

```javascript
await expect(page.locator('.alert-success')).toBeVisible();
await expect(page.locator('.alert-success')).toContainText(/Success/i);
```

#### Form Submission

```javascript
await page.selectOption('select[name="currency"]', 'PLN');
await page.fill('input[name="amount"]', '100.00');
await page.click('button[type="submit"]');
```

#### Waiting for Navigation

```javascript
await page.waitForURL('**/dashboard');
await page.waitForLoadState('networkidle');
```

## Test Data

### Test Users

The tests assume the following test users exist:

**Customer:**
- Username: `testcustomer`
- Password: `TestPass123!`

**Employee:**
- Username: `testemployee`
- Password: `TestPass123!`

### Setting Up Test Data

Before running tests, ensure test data is available:

```bash
# Load fixtures in development environment
make fixtures
```

## Configuration

The Playwright configuration is in `playwright.config.js`:

```javascript
export default defineConfig({
  testDir: './e2e',
  baseURL: 'http://localhost:3000',
  use: {
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    { name: 'webkit', use: { ...devices['Desktop Safari'] } },
  ],
});
```

## CI/CD Integration

Tests are configured to run in CI with:
- Automatic retries (2 retries)
- Sequential execution (workers: 1)
- HTML and list reporters

## Debugging Tests

### Visual Debugging

```bash
# Run with UI mode for visual debugging
make e2e-ui
```

### Debug Specific Test

```bash
# Run specific test file
npx playwright test e2e/auth.spec.js

# Run specific test by name
npx playwright test -g "should login as customer"
```

### Inspect Element

```bash
# Open Playwright Inspector
make e2e-debug
```

### Screenshots and Videos

Screenshots and videos are automatically captured on test failure and saved in:
- `test-results/` - Test artifacts
- `playwright-report/` - HTML report with embedded media

## Best Practices

1. **Use data-testid for stable selectors:**
   ```javascript
   await page.click('[data-testid="submit-button"]');
   ```

2. **Wait for elements properly:**
   ```javascript
   await expect(page.locator('.spinner')).not.toBeVisible();
   await expect(page.getByText('Loaded')).toBeVisible();
   ```

3. **Use semantic locators:**
   ```javascript
   await page.getByRole('button', { name: 'Submit' }).click();
   await page.getByLabel('Username').fill('user');
   ```

4. **Group related tests:**
   ```javascript
   test.describe('Feature', () => {
     test.describe('Sub-feature', () => {
       test('specific test', async ({ page }) => {});
     });
   });
   ```

5. **Clean up test data:**
   - Use unique identifiers (timestamps)
   - Clean up in test teardown if needed

## Troubleshooting

### Tests Timing Out

- Increase timeout in config: `timeout: 60000`
- Check if backend is running: `curl http://localhost:8080/api/health`
- Check if frontend is running: `curl http://localhost:3000`

### Elements Not Found

- Use Playwright Inspector to verify selectors
- Wait for dynamic content to load
- Check if element is in viewport

### Flaky Tests

- Add proper waits for async operations
- Use `waitForLoadState('networkidle')`
- Avoid hard-coded waits (`page.waitForTimeout`)

### Browser Not Installed

```bash
# Reinstall browsers
npx playwright install --with-deps chromium
```

## Additional Resources

- [Playwright Documentation](https://playwright.dev/)
- [Playwright Best Practices](https://playwright.dev/docs/best-practices)
- [Playwright API Reference](https://playwright.dev/docs/api/class-playwright)

## Migration from PHPUnit Tests

The e2e tests maintain the same structure and coverage as the original PHPUnit Presentation tests:

| PHPUnit Test | E2E Test | Status |
|--------------|----------|--------|
| SecurityControllerTest | auth.spec.js | ✅ Migrated |
| CustomerDashboardControllerTest | customer-dashboard.spec.js | ✅ Migrated |
| TransactionControllerTest | customer-transactions.spec.js | ✅ Migrated |
| EmployeeDashboardControllerTest | employee-operations.spec.js | ✅ Migrated |
| EmployeeTransactionControllerTest | employee-operations.spec.js | ✅ Migrated |
| BankAccountControllerTest | bank-account-management.spec.js | ✅ Migrated |

Each test maintains the same test cases and scenarios, adapted for the React frontend's URL structure and component behavior.
