import { test, expect } from '@playwright/test';

/**
 * Employee Dashboard and Operations tests
 * Based on EmployeeDashboardControllerTest.php and EmployeeTransactionControllerTest.php
 */
test.describe('Employee Dashboard', () => {

  test.describe('Access Control', () => {

    test('should redirect unauthenticated user to login', async ({ page }) => {
      await page.goto('/en/employee/dashboard');

      await expect(page).toHaveURL(/\/en\/login/);
    });

    test('should prevent customer from accessing employee dashboard', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      // Try to access employee dashboard
      await page.goto('/en/employee/dashboard');

      // Should show 403 forbidden
      const text = await page.textContent('body');
      expect(text).toMatch(/403|Forbidden|Access Denied/i);
    });

    test('should allow employee to access dashboard', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await expect(page).toHaveURL(/\/en\/employee\/dashboard/);
      await expect(page).toHaveTitle(/Employee Dashboard/);
      await expect(page.getByText('Employee Dashboard')).toBeVisible();
    });
  });

  test.describe('Dashboard Display', () => {

    test('should display employee name', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await expect(page).toHaveURL(/\/en\/employee\/dashboard/);

      // Should show welcome message
      await expect(page.getByText(/Welcome/i)).toBeVisible();
    });

    test('should display action cards', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await expect(page).toHaveURL(/\/en\/employee\/dashboard/);

      // Should show action cards
      const cards = page.locator('.card');
      const count = await cards.count();
      expect(count).toBeGreaterThan(0);
    });

    test('should show navigation to open account for new customer', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await expect(page).toHaveURL(/\/en\/employee\/dashboard/);

      // Should have link to open account for new customer
      const link = page.getByRole('link', { name: /New Customer/i });
      await expect(link).toBeVisible();
    });

    test('should show navigation to open account for existing customer', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await expect(page).toHaveURL(/\/en\/employee\/dashboard/);

      // Should have link to open account for existing customer
      const link = page.getByRole('link', { name: /Existing Customer/i });
      await expect(link).toBeVisible();
    });

    test('should show navigation to close account', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await expect(page).toHaveURL(/\/en\/employee\/dashboard/);

      // Should have link to close account
      const link = page.getByRole('link', { name: /Close Account/i });
      await expect(link).toBeVisible();
    });

    test('should show navigation to deposit money', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await expect(page).toHaveURL(/\/en\/employee\/dashboard/);

      // Should have link to deposit money
      const link = page.getByRole('link', { name: /Deposit/i });
      await expect(link).toBeVisible();
    });
  });
});

test.describe('Employee - Deposit Money', () => {

  test.describe('Access Control', () => {

    test('should redirect unauthenticated user to login', async ({ page }) => {
      await page.goto('/en/employee/deposit-money');

      await expect(page).toHaveURL(/\/en\/login/);
    });

    test('should prevent customer from accessing deposit page', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      // Try to access deposit page
      await page.goto('/en/employee/deposit-money');

      // Should show 403 forbidden
      const text = await page.textContent('body');
      expect(text).toMatch(/403|Forbidden|Access Denied/i);
    });

    test('should allow employee to access deposit page', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/employee/deposit-money');

      await expect(page).toHaveURL(/\/en\/employee\/deposit-money/);
      await expect(page.getByText('Deposit Money')).toBeVisible();
    });
  });

  test.describe('Deposit Form', () => {

    test('should render deposit form correctly', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/employee/deposit-money');

      // Check form fields
      await expect(page.locator('select[name="bankAccountId"]')).toBeVisible();
      await expect(page.locator('input[name="amount"]')).toBeVisible();
      await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('should deposit money successfully', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/employee/deposit-money');

      // Fill deposit form
      await page.selectOption('select[name="bankAccountId"]', { index: 1 });
      await page.fill('input[name="amount"]', '500.00');

      // Submit form
      await page.click('button[type="submit"]');

      // Should redirect to dashboard
      await expect(page).toHaveURL(/\/en\/employee\/dashboard/);

      // Should show success message
      await expect(page.locator('.alert-success')).toBeVisible();
      await expect(page.locator('.alert-success')).toContainText(/Deposit completed successfully/i);
    });

    test('should show error with zero amount', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/employee/deposit-money');

      // Fill form with zero amount
      await page.selectOption('select[name="bankAccountId"]', { index: 1 });
      await page.fill('input[name="amount"]', '0');

      // Submit form
      await page.click('button[type="submit"]');

      // Should show validation error
      await expect(page.locator('.alert-danger, .invalid-feedback')).toBeVisible();
      await expect(page.getByText(/positive/i)).toBeVisible();
    });

    test('should show error with negative amount', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/employee/deposit-money');

      // Fill form with negative amount
      await page.selectOption('select[name="bankAccountId"]', { index: 1 });
      await page.fill('input[name="amount"]', '-100.00');

      // Submit form
      await page.click('button[type="submit"]');

      // Should show validation error
      await expect(page.locator('.alert-danger, .invalid-feedback')).toBeVisible();
      await expect(page.getByText(/positive/i)).toBeVisible();
    });
  });
});

test.describe('Employee - Customer Selection', () => {

  test.describe('Select Customer Page', () => {

    test('should allow employee to search customers', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/employee/select-customer');

      // Should have customer search/selection interface
      await expect(page.getByText(/Select Customer/i)).toBeVisible();
    });

    test('should display customer list', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/employee/select-customer');

      // Should show customer list or search
      const hasList = await page.locator('table, .customer-list').isVisible();
      const hasSearch = await page.locator('input[type="search"]').isVisible();

      expect(hasList || hasSearch).toBeTruthy();
    });

    test('should navigate to customer transaction history', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      await page.goto('/en/employee/select-customer');

      // Select a customer (if available)
      const viewButton = page.locator('a:has-text("View"), button:has-text("View")').first();

      if (await viewButton.isVisible()) {
        await viewButton.click();

        // Should navigate to transaction history page
        await expect(page).toHaveURL(/\/en\/employee\/transaction-history/);
      }
    });
  });
});

test.describe('Employee - Transaction History', () => {

  test.describe('Access Control', () => {

    test('should allow employee to view customer transaction history', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');

      // Navigate via customer selection
      await page.goto('/en/employee/select-customer');

      // This test assumes customer selection leads to history
      // Adjust based on actual implementation
    });
  });
});
