import { test, expect } from '@playwright/test';

/**
 * Authentication tests based on SecurityControllerTest.php
 */
test.describe('Authentication', () => {
  
  test.describe('Login Page', () => {
    
    test('should render login page correctly', async ({ page }) => {
      await page.goto('/en/login');
      
      // Check page title
      await expect(page).toHaveTitle(/Log In/);
      
      // Check main elements
      await expect(page.getByText('Easy Banking System')).toBeVisible();
      await expect(page.getByText('Sign In')).toBeVisible();
      
      // Check form fields exist
      await expect(page.locator('input[name="username"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
      await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('should support Polish locale', async ({ page }) => {
      await page.goto('/pl/logowanie');
      
      await expect(page).toHaveTitle(/Zaloguj się/);
      await expect(page.getByText('Easy Banking System')).toBeVisible();
    });
  });

  test.describe('Login Flow', () => {
    
    test('should login as customer and redirect to dashboard', async ({ page }) => {
      // Note: This requires a test customer to exist
      // For now using placeholder credentials - adjust based on test data setup
      await page.goto('/en/login');
      
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      // Should redirect to customer dashboard
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      await expect(page.getByText('Customer Dashboard')).toBeVisible();
    });

    test('should login as employee and redirect to dashboard', async ({ page }) => {
      // Note: This requires a test employee to exist
      await page.goto('/en/login');
      
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      // Should redirect to employee dashboard
      await expect(page).toHaveURL(/\/en\/employee\/dashboard/);
      await expect(page.getByText('Employee Dashboard')).toBeVisible();
    });

    test('should show error with invalid credentials', async ({ page }) => {
      await page.goto('/en/login');
      
      await page.fill('input[name="username"]', 'nonexistent');
      await page.fill('input[name="password"]', 'wrongpassword');
      await page.click('button[type="submit"]');
      
      // Should show error message
      await expect(page.locator('.alert-danger')).toBeVisible();
      await expect(page.locator('.alert-danger')).toContainText(/Invalid credentials/);
    });

    test('should show error with non-existent user', async ({ page }) => {
      await page.goto('/en/login');
      
      await page.fill('input[name="username"]', 'doesnotexist123');
      await page.fill('input[name="password"]', 'somepassword');
      await page.click('button[type="submit"]');
      
      // Should show error message
      await expect(page.locator('.alert-danger')).toBeVisible();
      await expect(page.locator('.alert-danger')).toContainText(/Invalid credentials/);
    });

    test('should preserve username on login error', async ({ page }) => {
      await page.goto('/en/login');
      
      const username = 'testuser';
      await page.fill('input[name="username"]', username);
      await page.fill('input[name="password"]', 'wrongpassword');
      await page.click('button[type="submit"]');
      
      // Username should be preserved in the form
      const usernameInput = page.locator('input[name="username"]');
      await expect(usernameInput).toHaveValue(username);
    });
  });

  test.describe('Logout', () => {
    
    test('should logout and redirect to login', async ({ page }) => {
      // Login first
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Logout
      await page.click('a:has-text("Logout")');
      
      // Should redirect to login
      await expect(page).toHaveURL(/\/en\/login/);
      
      // Try to access protected page - should redirect to login
      await page.goto('/en/customer/dashboard');
      await expect(page).toHaveURL(/\/en\/login/);
    });
  });

  test.describe('Route Protection', () => {
    
    test('should redirect unauthenticated user from protected routes', async ({ page }) => {
      // Try to access customer dashboard without login
      await page.goto('/en/customer/dashboard');
      
      // Should redirect to login
      await expect(page).toHaveURL(/\/en\/login/);
    });

    test('should redirect authenticated user from login page', async ({ page }) => {
      // Login first
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Try to go to login page again
      await page.goto('/en/login');
      
      // Should redirect away from login (to dashboard or home)
      await expect(page).not.toHaveURL(/\/en\/login/);
    });

    test('should prevent customer from accessing employee routes', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/customer\/dashboard/);
      
      // Try to access employee dashboard
      await page.goto('/en/employee/dashboard');
      
      // Should show 403 forbidden or redirect
      const text = await page.textContent('body');
      expect(text).toMatch(/403|Forbidden|Access Denied/i);
    });

    test('should prevent employee from accessing customer routes', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/en\/employee\/dashboard/);
      
      // Try to access customer dashboard
      await page.goto('/en/customer/dashboard');
      
      // Should show 403 forbidden or redirect
      const text = await page.textContent('body');
      expect(text).toMatch(/403|Forbidden|Access Denied/i);
    });
  });

  test.describe('Home Page Redirect', () => {
    
    test('should redirect unauthenticated user to login', async ({ page }) => {
      await page.goto('/');
      
      await expect(page).toHaveURL(/\/login/);
    });

    test('should redirect customer to customer dashboard', async ({ page }) => {
      // Login as customer
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testcustomer');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      // Go to home
      await page.goto('/');
      
      // Should redirect to customer dashboard
      await expect(page).toHaveURL(/\/customer\/dashboard/);
    });

    test('should redirect employee to employee dashboard', async ({ page }) => {
      // Login as employee
      await page.goto('/en/login');
      await page.fill('input[name="username"]', 'testemployee');
      await page.fill('input[name="password"]', 'TestPass123!');
      await page.click('button[type="submit"]');
      
      // Go to home
      await page.goto('/');
      
      // Should redirect to employee dashboard
      await expect(page).toHaveURL(/\/employee\/dashboard/);
    });
  });
});
