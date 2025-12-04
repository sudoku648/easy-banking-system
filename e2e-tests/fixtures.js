import { test as base } from '@playwright/test';
import { apiClient } from './utils/api-client';

/**
 * Extended Playwright test with custom fixtures
 */
export const test = base.extend({
  /**
   * Create a customer user before each test
   */
  customerUser: async ({ page }, use) => {
    const username = `customer_${Date.now()}`;
    const password = 'TestPass123!';

    // Create customer via API
    const customer = await apiClient.createCustomer({
      username,
      password,
      firstName: 'Test',
      lastName: 'Customer',
      currency: 'PLN',
      permanentResidenceStreet: 'Test Street 123',
      permanentResidenceCity: 'Warsaw',
      permanentResidencePostalCode: '00-001',
      permanentResidenceCountry: 'Poland',
      correspondenceAddresses: [{
        street: 'Test Street 123',
        city: 'Warsaw',
        postalCode: '00-001',
        country: 'Poland'
      }]
    });

    await use({ ...customer, password });

    // Cleanup if needed
  },

  /**
   * Create an employee user before each test
   */
  employeeUser: async ({ page }, use) => {
    const username = `employee_${Date.now()}`;
    const password = 'TestPass123!';

    // Create employee via CLI or API
    const employee = await apiClient.createEmployee({
      username,
      password,
      firstName: 'Test',
      lastName: 'Employee'
    });

    await use({ ...employee, password });

    // Cleanup if needed
  },

  /**
   * Authenticated customer page
   */
  authenticatedCustomerPage: async ({ page, customerUser }, use) => {
    // Login as customer
    await page.goto('/en/login');
    await page.fill('input[name="username"]', customerUser.username);
    await page.fill('input[name="password"]', customerUser.password);
    await page.click('button[type="submit"]');

    // Wait for redirect to dashboard
    await page.waitForURL('**/en/customer/dashboard');

    await use(page);
  },

  /**
   * Authenticated employee page
   */
  authenticatedEmployeePage: async ({ page, employeeUser }, use) => {
    // Login as employee
    await page.goto('/en/login');
    await page.fill('input[name="username"]', employeeUser.username);
    await page.fill('input[name="password"]', employeeUser.password);
    await page.click('button[type="submit"]');

    // Wait for redirect to dashboard
    await page.waitForURL('**/en/employee/dashboard');

    await use(page);
  }
});

export { expect } from '@playwright/test';
