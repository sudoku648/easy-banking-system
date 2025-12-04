import { test, expect } from '@playwright/test';

/**
 * Customer Dashboard tests based on CustomerDashboardControllerTest.php
 */
test.describe('Customer Dashboard', () => {
  
  test.describe('Access Control', () => {
    
    test('should redirect unauthenticated user to login', async ({ page }) => {
      await page.goto('/en/customer/dashboard');
      
      await expect(page).toHaveURL(/\/en\/login/);
    });

    test('should prevent employee from accessing customer dashboard', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      // Try to access customer dashboard
      await page.goto('/en/customer/dashboard');
      
      // Should show 403 forbidden
      const text = await page.textContent('body');
      expect(text).toMatch(/403|Forbidden|Access Denied/i);
    });

    test('should allow customer to access dashboard', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      await expect(page).toHaveTitle(/Customer Dashboard/);
      await expect(page.getByText('Customer Dashboard')).toBeVisible();
    });
  });

  test.describe('Bank Accounts Display', () => {
    
    test('should show message when customer has no accounts', async ({ page }) => {
      // Login as customer with no accounts
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'newcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Should show no accounts message
      await expect(page.getByText(/don't have any bank accounts yet/i)).toBeVisible();
    });

    test('should display customer bank accounts', async ({ page }) => {
      // Login as customer with accounts
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Should not show "no accounts" message
      await expect(page.getByText(/No bank accounts/i)).not.toBeVisible();
      
      // Should show account table
      await expect(page.locator('table')).toBeVisible();
      
      // Should show IBAN column
      await expect(page.getByText('IBAN')).toBeVisible();
      
      // Should show Balance column
      await expect(page.getByText('Balance')).toBeVisible();
      
      // Should show Currency column
      await expect(page.getByText('Currency')).toBeVisible();
      
      // Should show Status column
      await expect(page.getByText('Status')).toBeVisible();
    });

    test('should display multiple bank accounts', async ({ page }) => {
      // Login as customer with multiple accounts
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Should show multiple currency badges
      const currencyBadges = page.locator('.badge');
      const count = await currencyBadges.count();
      expect(count).toBeGreaterThan(0);
    });

    test('should display account status as active', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Should show active status
      await expect(page.getByText('Active')).toBeVisible();
    });

    test('should display account balance', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Should show balance (formatted as decimal with 2 decimals)
      const balance = page.locator('td.text-end').first();
      await expect(balance).toBeVisible();
      
      // Balance should match pattern like "0.00" or "1,234.56"
      const balanceText = await balance.textContent();
      expect(balanceText).toMatch(/[\d,]+\.\d{2}/);
    });
  });

  test.describe('Navigation', () => {
    
    test('should show navigation to transfer money', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Should show "Transfer Money" link/button
      const transferLink = page.getByRole('link', { name: /Transfer Money/i });
      await expect(transferLink).toBeVisible();
      
      // Click should navigate to transfer page
      await transferLink.click();
      await expect(page).toHaveURL(/\/en\/customer\/transfer-money/);
    });

    test('should show navigation to transaction history', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Should show "Transaction History" link/button
      const historyLink = page.getByRole('link', { name: /Transaction History/i });
      await expect(historyLink).toBeVisible();
      
      // Click should navigate to history page
      await historyLink.click();
      await expect(page).toHaveURL(/\/en\/customer\/transaction-history/);
    });

    test('should show transfer button for active accounts', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Active accounts should have transfer button
      const transferButton = page.locator('a.btn:has-text("Transfer")').first();
      await expect(transferButton).toBeVisible();
    });
  });

  test.describe('User Information', () => {
    
    test('should display customer name', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Should show welcome message with customer name
      await expect(page.getByText(/Welcome/i)).toBeVisible();
    });
  });

  test.describe('Quick Actions', () => {
    
    test('should display quick action cards', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Should show action cards (Bootstrap cards)
      const cards = page.locator('.card');
      const count = await cards.count();
      expect(count).toBeGreaterThan(0);
    });

    test('should have working quick action links', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // All links in cards should be functional (not broken)
      const links = page.locator('.card a.btn');
      const count = await links.count();
      
      for (let i = 0; i < count; i++) {
        const link = links.nth(i);
        const href = await link.getAttribute('href');
        expect(href).toBeTruthy();
        expect(href).toMatch(/^\/[a-z]{2}\//); // Should start with locale prefix
      }
    });
  });

  test.describe('Responsive Design', () => {
    
    test('should display correctly on mobile', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 }); // iPhone SE size
      
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Dashboard should be visible and usable
      await expect(page.getByText('Customer Dashboard')).toBeVisible();
      
      // Cards should be stacked vertically (Bootstrap responsive)
      const cards = page.locator('.card');
      await expect(cards.first()).toBeVisible();
    });
  });
});
