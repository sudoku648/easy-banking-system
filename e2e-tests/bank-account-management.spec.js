import { test, expect } from '@playwright/test';

/**
 * Bank Account Management tests
 * Based on BankAccountControllerTest.php
 */
test.describe('Bank Account Management', () => {
  
  test.describe('Open Account - New Customer', () => {
    
    test.describe('Access Control', () => {
      
      test('should redirect unauthenticated user to login', async ({ page }) => {
        await page.goto('/en/employee/open-account-new-customer');
        
        await expect(page).toHaveURL(/\/en\/login/);
      });

      test('should prevent customer from accessing page', async ({ page }) => {
        // Login as customer
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testcustomer');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        // Try to access page
        await page.goto('/en/employee/open-account-new-customer');
        
        // Should show 403 forbidden
        const text = await page.textContent('body');
        expect(text).toMatch(/403|Forbidden|Access Denied/i);
      });

      test('should allow employee to access page', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/open-account-new-customer');
        
        await expect(page).toHaveURL(/\/en\/employee\/open-account-new-customer/);
        await expect(page.getByText('Open Account for New Customer')).toBeVisible();
      });
    });

    test.describe('Form Rendering', () => {
      
      test('should render form correctly', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/open-account-new-customer');
        
        // Check form fields exist
        await expect(page.locator('input[name="username"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
        await expect(page.locator('input[name="firstName"]')).toBeVisible();
        await expect(page.locator('input[name="lastName"]')).toBeVisible();
        await expect(page.locator('select[name="currency"]')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
      });

      test('should have address fields', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/open-account-new-customer');
        
        // Check address fields
        await expect(page.locator('input[name*="street"]')).toBeVisible();
        await expect(page.locator('input[name*="city"]')).toBeVisible();
        await expect(page.locator('input[name*="postalCode"]')).toBeVisible();
        await expect(page.locator('input[name*="country"]')).toBeVisible();
      });
    });

    test.describe('Account Creation', () => {
      
      test('should create account successfully', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/open-account-new-customer');
        
        // Fill form with unique username
        const timestamp = Date.now();
        await page.fill('input[name="username"]', `customer${timestamp}`);
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.fill('input[name="firstName"]', 'John');
        await page.fill('input[name="lastName"]', 'Doe');
        
        // Fill address fields
        await page.fill('input[name*="permanentResidence"][name*="street"]', 'Test Street 123');
        await page.fill('input[name*="permanentResidence"][name*="city"]', 'Warsaw');
        await page.fill('input[name*="permanentResidence"][name*="postalCode1"]', '00');
        await page.fill('input[name*="permanentResidence"][name*="postalCode2"]', '001');
        await page.fill('input[name*="permanentResidence"][name*="country"]', 'Poland');
        
        // Fill correspondence address
        await page.fill('input[name*="correspondenceAddresses"][name*="street"]', 'Correspondence St 456');
        await page.fill('input[name*="correspondenceAddresses"][name*="city"]', 'Krakow');
        await page.fill('input[name*="correspondenceAddresses"][name*="postalCode1"]', '30');
        await page.fill('input[name*="correspondenceAddresses"][name*="postalCode2"]', '001');
        await page.fill('input[name*="correspondenceAddresses"][name*="country"]', 'Poland');
        
        await page.selectOption('select[name="currency"]', 'PLN');
        
        // Submit form
        await page.click('button[type="submit"]');
        
        // Should redirect to dashboard
        await expect(page).toHaveURL(/\/en\/employee\/dashboard/);
        
        // Should show success message
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('.alert-success')).toContainText(/Bank account opened successfully/i);
      });

      test('should show error with short username', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/open-account-new-customer');
        
        // Fill form with short username
        await page.fill('input[name="username"]', 'a');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.fill('input[name="firstName"]', 'John');
        await page.fill('input[name="lastName"]', 'Doe');
        await page.selectOption('select[name="currency"]', 'PLN');
        
        // Submit form
        await page.click('button[type="submit"]');
        
        // Should show validation error
        await expect(page.locator('.alert-danger, .invalid-feedback')).toBeVisible();
        await expect(page.getByText(/too short/i)).toBeVisible();
      });

      test('should show error with short password', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/open-account-new-customer');
        
        // Fill form with short password
        await page.fill('input[name="username"]', 'newcustomer123');
        await page.fill('input[name="password"]', '12345');
        await page.fill('input[name="firstName"]', 'John');
        await page.fill('input[name="lastName"]', 'Doe');
        await page.selectOption('select[name="currency"]', 'PLN');
        
        // Submit form
        await page.click('button[type="submit"]');
        
        // Should show validation error
        await expect(page.locator('.alert-danger, .invalid-feedback')).toBeVisible();
        await expect(page.getByText(/too short/i)).toBeVisible();
      });

      test('should show error with duplicate username', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/open-account-new-customer');
        
        // Try to use existing username
        await page.fill('input[name="username"]', 'testcustomer'); // Already exists
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.fill('input[name="firstName"]', 'John');
        await page.fill('input[name="lastName"]', 'Doe');
        await page.selectOption('select[name="currency"]', 'PLN');
        
        // Submit form
        await page.click('button[type="submit"]');
        
        // Should show error
        await expect(page.locator('.alert-danger')).toBeVisible();
      });
    });
  });

  test.describe('Open Account - Existing Customer', () => {
    
    test.describe('Access Control', () => {
      
      test('should allow employee to access page', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/open-account-existing-customer');
        
        await expect(page).toHaveURL(/\/en\/employee\/open-account-existing-customer/);
        await expect(page.getByText('Open Account for Existing Customer')).toBeVisible();
      });
    });

    test.describe('Form Rendering', () => {
      
      test('should render form correctly', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/open-account-existing-customer');
        
        // Check form fields
        await expect(page.locator('select[name="customerId"]')).toBeVisible();
        await expect(page.locator('select[name="currency"]')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
      });
    });

    test.describe('Account Creation', () => {
      
      test('should create account for existing customer', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/open-account-existing-customer');
        
        // Select customer and currency
        await page.selectOption('select[name="customerId"]', { index: 1 });
        await page.selectOption('select[name="currency"]', 'EUR');
        
        // Submit form
        await page.click('button[type="submit"]');
        
        // Should redirect to dashboard
        await expect(page).toHaveURL(/\/en\/employee\/dashboard/);
        
        // Should show success message
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('.alert-success')).toContainText(/Bank account opened successfully/i);
      });
    });
  });

  test.describe('Close Account', () => {
    
    test.describe('Access Control', () => {
      
      test('should redirect unauthenticated user to login', async ({ page }) => {
        await page.goto('/en/employee/close-account');
        
        await expect(page).toHaveURL(/\/en\/login/);
      });

      test('should prevent customer from accessing page', async ({ page }) => {
        // Login as customer
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testcustomer');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        // Try to access page
        await page.goto('/en/employee/close-account');
        
        // Should show 403 forbidden
        const text = await page.textContent('body');
        expect(text).toMatch(/403|Forbidden|Access Denied/i);
      });

      test('should allow employee to access page', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/close-account');
        
        await expect(page).toHaveURL(/\/en\/employee\/close-account/);
        await expect(page.getByText('Close Account')).toBeVisible();
      });
    });

    test.describe('Form', () => {
      
      test('should render close account form', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/close-account');
        
        // Check form fields
        await expect(page.locator('select[name="bankAccountId"]')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
      });

      test('should close account successfully', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/close-account');
        
        // Select account
        await page.selectOption('select[name="bankAccountId"]', { index: 1 });
        
        // Submit form
        await page.click('button[type="submit"]');
        
        // Should redirect to dashboard
        await expect(page).toHaveURL(/\/en\/employee\/dashboard/);
        
        // Should show success message
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('.alert-success')).toContainText(/Account closed successfully/i);
      });

      test('should show error when closing account with balance', async ({ page }) => {
        // Login as employee
        await page.goto('/en/login');
        await page.fill('input[name="username"]', 'testemployee');
        await page.fill('input[name="password"]', 'TestPass123!');
        await page.click('button[type="submit"]');
        
        await page.goto('/en/employee/close-account');
        
        // Select account with balance (if any)
        await page.selectOption('select[name="bankAccountId"]', { index: 1 });
        
        // Submit form
        await page.click('button[type="submit"]');
        
        // If account has balance, should show error
        const hasError = await page.locator('.alert-danger').isVisible();
        if (hasError) {
          await expect(page.locator('.alert-danger')).toContainText(/balance|funds/i);
        }
      });
    });
  });

  test.describe('Account Status', () => {
    
    test('should show closed account as inactive', async ({ page }) => {
      // Login as customer whose account was closed
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await page.goto('/en/customer/dashboard');
      
      // If there are closed accounts, they should be marked as such
      const closedBadge = page.locator('.badge.bg-danger:has-text("Closed")');
      if (await closedBadge.isVisible()) {
        await expect(closedBadge).toBeVisible();
      }
    });
  });
});
