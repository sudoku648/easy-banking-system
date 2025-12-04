/**
 * Helper functions for e2e tests
 */

/**
 * Wait for element to be visible
 */
export async function waitForElement(page, selector, timeout = 5000) {
  await page.waitForSelector(selector, { state: 'visible', timeout });
}

/**
 * Fill form field
 */
export async function fillField(page, selector, value) {
  await page.fill(selector, value);
}

/**
 * Click button with text
 */
export async function clickButton(page, text) {
  await page.click(`button:has-text("${text}")`);
}

/**
 * Check if element contains text
 */
export async function expectTextContent(page, selector, text) {
  const element = await page.locator(selector);
  await expect(element).toContainText(text);
}

/**
 * Wait for URL pattern
 */
export async function waitForUrl(page, pattern, timeout = 5000) {
  await page.waitForURL(pattern, { timeout });
}

/**
 * Check if alert/flash message is visible
 */
export async function expectFlashMessage(page, type, message) {
  const alert = page.locator(`.alert.alert-${type}`);
  await expect(alert).toBeVisible();
  if (message) {
    await expect(alert).toContainText(message);
  }
}

/**
 * Generate random string
 */
export function randomString(length = 10) {
  return Math.random().toString(36).substring(2, length + 2);
}

/**
 * Format money amount
 */
export function formatMoney(amount, currency = 'PLN') {
  return new Intl.NumberFormat('en-US', {
    style: 'decimal',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
}

/**
 * Wait for navigation to complete
 */
export async function waitForNavigation(page) {
  await page.waitForLoadState('networkidle');
}

/**
 * Take screenshot with timestamp
 */
export async function takeScreenshot(page, name) {
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
  await page.screenshot({
    path: `e2e/screenshots/${name}-${timestamp}.png`,
    fullPage: true
  });
}
