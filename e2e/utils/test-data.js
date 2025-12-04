/**
 * Test data constants and helpers
 */

// Default test users
export const TEST_USERS = {
  CUSTOMER: {
    username: 'testcustomer',
    password: 'password123',
    role: 'CUSTOMER'
  },
  EMPLOYEE: {
    username: 'testemployee',
    password: 'password123',
    role: 'EMPLOYEE'
  }
};

// Valid test IBANs (for transfer testing)
export const TEST_IBANS = {
  VALID_PL: 'PL61109010140000071219812874',
  VALID_DE: 'DE89370400440532013000',
  VALID_FR: 'FR1420041010050500013M02606',
  INVALID: 'INVALID_IBAN'
};

// Test currencies
export const TEST_CURRENCIES = {
  PLN: 'PLN',
  EUR: 'EUR',
  USD: 'USD',
  GBP: 'GBP'
};

// Test amounts
export const TEST_AMOUNTS = {
  SMALL: '10.00',
  MEDIUM: '100.00',
  LARGE: '1000.00',
  VERY_LARGE: '999999.00',
  ZERO: '0',
  NEGATIVE: '-50.00'
};

// URL patterns
export const URL_PATTERNS = {
  LOGIN: {
    en: '/en/login',
    pl: '/pl/logowanie'
  },
  CUSTOMER_DASHBOARD: {
    en: '/en/customer/dashboard',
    pl: '/pl/klient/panel'
  },
  EMPLOYEE_DASHBOARD: {
    en: '/en/employee/dashboard',
    pl: '/pl/pracownik/panel'
  },
  TRANSFER_MONEY: {
    en: '/en/customer/transfer-money',
    pl: '/pl/klient/przelew'
  },
  TRANSACTION_HISTORY: {
    en: '/en/customer/transaction-history',
    pl: '/pl/klient/historia-transakcji'
  }
};

// Validation messages
export const VALIDATION_MESSAGES = {
  INVALID_CREDENTIALS: /Invalid credentials/i,
  TOO_SHORT: /too short/i,
  POSITIVE_VALUE: /positive/i,
  INSUFFICIENT_FUNDS: /insufficient|balance|funds/i,
  ACCESS_DENIED: /403|Forbidden|Access Denied/i,
  SUCCESS: /success/i,
  ERROR: /error/i
};

// Helper to generate unique username
export function generateUniqueUsername(prefix = 'user') {
  return `${prefix}_${Date.now()}`;
}

// Helper to generate unique email
export function generateUniqueEmail(prefix = 'user') {
  return `${prefix}_${Date.now()}@example.com`;
}

// Helper to wait for API response
export async function waitForApiResponse(page, urlPattern, timeout = 5000) {
  return page.waitForResponse(
    response => response.url().includes(urlPattern) && response.status() === 200,
    { timeout }
  );
}

// Helper to login as customer
export async function loginAsCustomer(page, username = TEST_USERS.CUSTOMER.username, password = TEST_USERS.CUSTOMER.password) {
  await page.goto(URL_PATTERNS.LOGIN.en);
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForURL(new RegExp(URL_PATTERNS.CUSTOMER_DASHBOARD.en.replace(/\//g, '\\/')));
}

// Helper to login as employee
export async function loginAsEmployee(page, username = TEST_USERS.EMPLOYEE.username, password = TEST_USERS.EMPLOYEE.password) {
  await page.goto(URL_PATTERNS.LOGIN.en);
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForURL(new RegExp(URL_PATTERNS.EMPLOYEE_DASHBOARD.en.replace(/\//g, '\\/')));
}

// Helper to logout
export async function logout(page) {
  await page.click('a:has-text("Logout")');
  await page.waitForURL(new RegExp(URL_PATTERNS.LOGIN.en.replace(/\//g, '\\/')));
}

// Helper to check if element is visible
export async function isVisible(page, selector) {
  try {
    await page.waitForSelector(selector, { state: 'visible', timeout: 5000 });
    return true;
  } catch {
    return false;
  }
}

// Helper to get flash message
export async function getFlashMessage(page, type = 'success') {
  const alert = page.locator(`.alert.alert-${type}`);
  if (await alert.isVisible()) {
    return alert.textContent();
  }
  return null;
}

// Helper to fill address form
export async function fillAddressForm(page, prefix, address) {
  await page.fill(`input[name*="${prefix}"][name*="street"]`, address.street);
  await page.fill(`input[name*="${prefix}"][name*="city"]`, address.city);
  await page.fill(`input[name*="${prefix}"][name*="postalCode1"]`, address.postalCode1);
  await page.fill(`input[name*="${prefix}"][name*="postalCode2"]`, address.postalCode2);
  await page.fill(`input[name*="${prefix}"][name*="country"]`, address.country);
}

// Default test address
export const TEST_ADDRESS = {
  street: 'Test Street 123',
  city: 'Warsaw',
  postalCode1: '00',
  postalCode2: '001',
  country: 'Poland'
};

// Helper to format money
export function formatMoney(amount, locale = 'en-US') {
  return new Intl.NumberFormat(locale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount);
}
