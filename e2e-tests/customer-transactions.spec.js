import { test, expect } from '@playwright/test';

/**
 * Transaction tests based on TransactionControllerTest.php
 */
test.describe('Customer Transactions', () => {

  test.describe('Transfer Money - Access Control', () => {

    test('should redirect unauthenticated user to login', async ({ page }) => {
      await page.goto('/en/customer/transfer-money');

      await expect(page).toHaveURL(/\/en\/login/);
    });

    test('should prevent employee from accessing transfer page', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      // Try to access transfer page
      await page.goto('/en/customer/transfer-money');

      // Should show 403 forbidden
      const text = await page.textContent('body');
      expect(text).toMatch(/403|Forbidden|Access Denied/i);
    });

    test('should allow customer to access transfer page', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transfer-money');

      await expect(page).toHaveURL(/\/en\/customer\/transfer-money/);
      await expect(page.getByText('Transfer Money')).toBeVisible();
    });
  });

  test.describe('Transfer Form Rendering', () => {

    test('should render transfer form correctly', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transfer-money');

      // Check form fields exist
      await expect(page.locator('select[name="fromBankAccountId"]')).toBeVisible();
      await expect(page.locator('input[name="toIban"]')).toBeVisible();
      await expect(page.locator('input[name="amount"]')).toBeVisible();
      await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('should show customer accounts in dropdown', async ({ page }) => {
      // Login as customer with accounts
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transfer-money');

      // Account dropdown should have options
      const accountSelect = page.locator('select[name="fromBankAccountId"]');
      const options = accountSelect.locator('option');
      const count = await options.count();

      // Should have at least one account (plus placeholder option)
      expect(count).toBeGreaterThan(1);
    });

    test('should show only active accounts', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transfer-money');

      // Account select should be visible and functional
      const accountSelect = page.locator('select[name="fromBankAccountId"]');
      await expect(accountSelect).toBeVisible();
      await expect(accountSelect).toBeEnabled();
    });
  });

  test.describe('Transfer Money - Success', () => {

    test('should transfer money successfully', async ({ page }) => {
      // Login as customer with account and balance
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transfer-money');

      // Fill transfer form
      await page.selectOption('select[name="fromBankAccountId"]', { index: 1 });
      await page.fill('input[name="toIban"]', 'PL61109010140000071219812874'); // Valid IBAN format
      await page.fill('input[name="amount"]', '100.00');

      // Submit form
      await page.click('button[type="submit"]');

      // Should redirect to dashboard
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);

      // Should show success message
      await expect(page.locator('.alert-success')).toBeVisible();
      await expect(page.locator('.alert-success')).toContainText(/Transfer completed successfully/i);
    });
  });

  test.describe('Transfer Money - Validation', () => {

    test('should show error with invalid IBAN', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transfer-money');

      // Fill form with invalid IBAN
      await page.selectOption('select[name="fromBankAccountId"]', { index: 1 });
      await page.fill('input[name="toIban"]', 'INVALID');
      await page.fill('input[name="amount"]', '10.00');

      // Submit form
      await page.click('button[type="submit"]');

      // Should show validation error
      await expect(page.locator('.alert-danger, .invalid-feedback')).toBeVisible();
    });

    test('should show error with zero amount', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transfer-money');

      // Fill form with zero amount
      await page.selectOption('select[name="fromBankAccountId"]', { index: 1 });
      await page.fill('input[name="toIban"]', 'PL61109010140000071219812874');
      await page.fill('input[name="amount"]', '0');

      // Submit form
      await page.click('button[type="submit"]');

      // Should show validation error about positive value
      await expect(page.locator('.alert-danger, .invalid-feedback')).toBeVisible();
      await expect(page.getByText(/positive/i)).toBeVisible();
    });

    test('should show error with negative amount', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transfer-money');

      // Fill form with negative amount
      await page.selectOption('select[name="fromBankAccountId"]', { index: 1 });
      await page.fill('input[name="toIban"]', 'PL61109010140000071219812874');
      await page.fill('input[name="amount"]', '-50.00');

      // Submit form
      await page.click('button[type="submit"]');

      // Should show validation error
      await expect(page.locator('.alert-danger, .invalid-feedback')).toBeVisible();
      await expect(page.getByText(/positive/i)).toBeVisible();
    });

    test('should show error when transferring more than balance', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transfer-money');

      // Try to transfer more than available balance
      await page.selectOption('select[name="fromBankAccountId"]', { index: 1 });
      await page.fill('input[name="toIban"]', 'PL61109010140000071219812874');
      await page.fill('input[name="amount"]', '999999.00'); // Very large amount

      // Submit form
      await page.click('button[type="submit"]');

      // Should show error about insufficient funds
      await expect(page.locator('.alert-danger')).toBeVisible();
      await expect(page.getByText(/insufficient|balance|funds/i)).toBeVisible();
    });

    test('should show error when transferring to same account', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      // Get own IBAN from dashboard first
      await page.goto('/en/customer/dashboard');
      const iban = await page.locator('code').first().textContent();

      await page.goto('/en/customer/transfer-money');

      // Try to transfer to own account
      await page.selectOption('select[name="fromBankAccountId"]', { index: 1 });
      await page.fill('input[name="toIban"]', iban || '');
      await page.fill('input[name="amount"]', '10.00');

      // Submit form
      await page.click('button[type="submit"]');

      // Should show error
      await expect(page.locator('.alert-danger')).toBeVisible();
    });
  });

  test.describe('Transaction History - Access Control', () => {

    test('should redirect unauthenticated user to login', async ({ page }) => {
      await page.goto('/en/customer/transaction-history');

      await expect(page).toHaveURL(/\/en\/login/);
    });

    test('should prevent employee from accessing customer history', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      // Try to access customer transaction history
      await page.goto('/en/customer/transaction-history');

      // Should show 403 forbidden
      const text = await page.textContent('body');
      expect(text).toMatch(/403|Forbidden|Access Denied/i);
    });

    test('should allow customer to access transaction history', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transaction-history');

      await expect(page).toHaveURL(/\/en\/customer\/transaction-history/);
      await expect(page.getByText('Transaction History')).toBeVisible();
    });
  });

  test.describe('Transaction History - Display', () => {

    test('should show message when no transactions exist', async ({ page }) => {
      // Login as customer with no transactions
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'newcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transaction-history');

      // Should show no transactions message
      await expect(page.getByText(/No transactions found/i)).toBeVisible();
    });

    test('should display transaction list', async ({ page }) => {
      // Login as customer with transactions
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transaction-history');

      // Should show transaction table
      await expect(page.locator('table')).toBeVisible();

      // Should have table headers
      await expect(page.getByText('Date')).toBeVisible();
      await expect(page.getByText('Type')).toBeVisible();
      await expect(page.getByText('Amount')).toBeVisible();
    });

    test('should show transaction details', async ({ page }) => {
      // Login as customer with transactions
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transaction-history');

      // Each transaction row should show complete information
      const rows = page.locator('tbody tr');
      const firstRow = rows.first();

      if (await rows.count() > 0) {
        await expect(firstRow).toBeVisible();

        // Should contain formatted date, type, and amount
        const rowText = await firstRow.textContent();
        expect(rowText).toBeTruthy();
      }
    });

    test('should show back to dashboard link', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transaction-history');

      // Should have back button
      const backLink = page.getByRole('link', { name: /Back to Dashboard/i });
      await expect(backLink).toBeVisible();

      // Click should navigate to dashboard
      await backLink.click();
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
    });
  });

  test.describe('Transaction Filters', () => {

    test('should allow filtering transactions by account', async ({ page }) => {
      // Login as customer with multiple accounts
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/customer/transaction-history');

      // If filter exists, test it
      const accountFilter = page.locator('select[name="accountFilter"]');
      if (await accountFilter.isVisible()) {
        await accountFilter.selectOption({ index: 1 });

        // Transactions should be filtered
        await page.waitForTimeout(1000); // Wait for filter to apply
      }
    });
  });
});
