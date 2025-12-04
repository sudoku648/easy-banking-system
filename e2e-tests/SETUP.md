# E2E Test Setup and Configuration

## Quick Start

Follow these steps to set up and run e2e tests:

### 1. Install Dependencies

```bash
# Install all Node.js dependencies
npm install
```

### 2. Install Playwright Browsers

```bash
# Install Playwright browsers (Chromium, Firefox, WebKit)
make e2e-install
```

This will download and install the required browsers and system dependencies.

### 3. Start Backend Services

```bash
# Start the development environment with backend API
make dev
```

This starts:
- PHP backend on `http://localhost:8080`
- PostgreSQL database on `localhost:54322`

### 4. Load Test Data (Optional but Recommended)

```bash
# Load fixtures for test users and accounts
make fixtures
```

This creates test users:
- **Customer**: `testcustomer` / `password123`
- **Employee**: `testemployee` / `password123`

### 5. Start Frontend Development Server

In a separate terminal:

```bash
# Start React frontend
make frontend-dev
```

This starts the frontend on `http://localhost:3000`

### 6. Run E2E Tests

```bash
# Run all tests
make e2e

# Or run with UI mode
make e2e-ui
```

## Environment Requirements

### System Requirements

- **Node.js**: v18 or higher
- **npm**: v9 or higher
- **Docker**: v20 or higher
- **Docker Compose**: v2 or higher

### Browser Requirements

Playwright will install:
- Chromium
- Firefox
- WebKit (Safari)

### Port Requirements

Ensure these ports are available:
- `3000` - Frontend development server
- `8080` - Backend API server
- `54322` - PostgreSQL database

## Configuration

### Playwright Config

The configuration file `playwright.config.js` includes:

```javascript
{
  testDir: './e2e',
  baseURL: 'http://localhost:3000',
  timeout: 30000,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
}
```

### Environment Variables

You can customize behavior with environment variables:

```bash
# Run in CI mode (with retries)
CI=1 npm run test:e2e

# Set custom base URL
BASE_URL=http://localhost:3001 npm run test:e2e

# Set custom API URL
API_BASE_URL=http://localhost:8081 npm run test:e2e
```

## Test Execution Modes

### Headless Mode (Default)

```bash
npm run test:e2e
```

Tests run in the background without visible browser windows.

### Headed Mode

```bash
npm run test:e2e:headed
```

Tests run with visible browser windows - useful for watching tests execute.

### UI Mode (Interactive)

```bash
npm run test:e2e:ui
```

Opens Playwright UI for:
- Running individual tests
- Debugging tests
- Time-travel debugging
- Viewing test reports

### Debug Mode

```bash
npm run test:e2e:debug
```

Opens Playwright Inspector for:
- Step-by-step test execution
- Pausing tests
- Inspecting elements
- Evaluating expressions

## Running Specific Tests

### Run Single Test File

```bash
npx playwright test e2e/auth.spec.js
```

### Run Tests by Name Pattern

```bash
npx playwright test -g "login"
```

### Run Tests in Specific Browser

```bash
npx playwright test --project=chromium
npx playwright test --project=firefox
npx playwright test --project=webkit
```

## Debugging Failed Tests

### View Test Report

```bash
make e2e-report
```

This opens an HTML report showing:
- Test results
- Screenshots of failures
- Video recordings
- Error messages and stack traces

### Analyze Artifacts

Failed tests generate artifacts in:

```
test-results/
├── [test-name]-[browser]-[retry]/
│   ├── video.webm
│   ├── trace.zip
│   └── test-failed-1.png
```

### View Trace

```bash
npx playwright show-trace test-results/[test-name]/trace.zip
```

This opens the Trace Viewer showing:
- Timeline of actions
- Network requests
- Console logs
- DOM snapshots

## CI/CD Integration

### GitHub Actions Example

```yaml
name: E2E Tests

on: [push, pull_request]

jobs:
  e2e:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'

      - name: Install dependencies
        run: npm ci

      - name: Install Playwright browsers
        run: npx playwright install --with-deps

      - name: Start services
        run: |
          make dev
          make frontend-dev &

      - name: Run e2e tests
        run: npm run test:e2e

      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: playwright-report/
```

## Troubleshooting

### "Browser not found" Error

```bash
# Reinstall browsers
npx playwright install --with-deps
```

### "Connection refused" Error

Check if services are running:

```bash
# Check backend
curl http://localhost:8080/api/health

# Check frontend
curl http://localhost:3000
```

### Tests Fail with Timeout

1. Increase timeout in `playwright.config.js`:
   ```javascript
   timeout: 60000 // 60 seconds
   ```

2. Or for specific test:
   ```javascript
   test('slow test', async ({ page }) => {
     test.setTimeout(60000);
     // test code
   });
   ```

### Database State Issues

Reset database and reload fixtures:

```bash
make dev-stop
make dev
make fixtures
```

### Port Already in Use

Kill processes using required ports:

```bash
# Kill process on port 3000
lsof -ti:3000 | xargs kill -9

# Kill process on port 8080
lsof -ti:8080 | xargs kill -9
```

## Best Practices

### 1. Test Isolation

Each test should be independent:
- Don't rely on execution order
- Clean up test data
- Use unique identifiers

### 2. Stable Selectors

Prefer stable selectors:
```javascript
// Good - semantic
await page.getByRole('button', { name: 'Submit' })

// Good - test ID
await page.locator('[data-testid="submit-btn"]')

// Bad - CSS class (can change)
await page.locator('.btn-primary')
```

### 3. Proper Waits

Wait for conditions, not time:
```javascript
// Good
await expect(page.locator('.loading')).not.toBeVisible()
await expect(page.getByText('Loaded')).toBeVisible()

// Bad
await page.waitForTimeout(3000)
```

### 4. Test Data Management

Use fixtures or API setup:
```javascript
test.beforeEach(async ({ page }) => {
  // Setup test data via API
  await apiClient.createTestUser()
})

test.afterEach(async ({ page }) => {
  // Cleanup
  await apiClient.cleanupTestData()
})
```

## Performance Optimization

### Parallel Execution

```javascript
// playwright.config.js
export default defineConfig({
  workers: 4, // Run 4 tests in parallel
  fullyParallel: true,
})
```

### Test Sharding (CI)

```bash
# Shard tests across 4 machines
npx playwright test --shard=1/4
npx playwright test --shard=2/4
npx playwright test --shard=3/4
npx playwright test --shard=4/4
```

### Reuse Authentication

```javascript
// Save auth state
const context = await browser.newContext()
await context.storageState({ path: 'auth.json' })

// Reuse auth state
const context = await browser.newContext({
  storageState: 'auth.json'
})
```

## Additional Resources

- [Playwright Documentation](https://playwright.dev/)
- [Playwright GitHub](https://github.com/microsoft/playwright)
- [Playwright Discord](https://aka.ms/playwright/discord)
- [Project README](../README.md)
- [E2E Test Guide](./README.md)
