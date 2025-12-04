# E2E Testing Quick Reference

## Setup

```bash
# Install dependencies
npm install

# Install browsers
make e2e-install
# or
npx playwright install --with-deps
```

## Running Tests

```bash
# Run all tests (headless)
make e2e
npm run test:e2e

# Run with UI mode (interactive)
make e2e-ui
npm run test:e2e:ui

# Run with visible browser
make e2e-headed
npm run test:e2e:headed

# Run in debug mode
make e2e-debug
npm run test:e2e:debug

# View test report
make e2e-report
npm run test:e2e:report
```

## Specific Tests

```bash
# Run single test file
npx playwright test e2e/auth.spec.js

# Run tests matching pattern
npx playwright test -g "login"

# Run in specific browser
npx playwright test --project=chromium
npx playwright test --project=firefox
npx playwright test --project=webkit

# Run specific test by line
npx playwright test e2e/auth.spec.js:15
```

## Debugging

```bash
# Debug mode
npx playwright test --debug

# Show trace
npx playwright show-trace test-results/[test]/trace.zip

# Generate report
npx playwright show-report

# Codegen (record tests)
npx playwright codegen http://localhost:3000
```

## Prerequisites

```bash
# Start backend
make dev

# Start frontend
make frontend-dev

# Load fixtures (test data)
make fixtures
```

## Test Users

**Customer:**
- Username: `testcustomer`
- Password: `password123`

**Employee:**
- Username: `testemployee`
- Password: `password123`

## Environment

- Frontend: http://localhost:3000
- Backend API: http://localhost:8080
- Database: localhost:54322

## Common Options

```bash
# Headed mode
npx playwright test --headed

# Specific browser
npx playwright test --project=chromium

# Workers (parallel tests)
npx playwright test --workers=4

# Max failures
npx playwright test --max-failures=5

# Retry failed tests
npx playwright test --retries=2

# Update snapshots
npx playwright test --update-snapshots

# List tests
npx playwright test --list

# Grep pattern
npx playwright test --grep "pattern"
npx playwright test --grep-invert "pattern"
```

## Test Structure

```
e2e/
├── auth.spec.js                    # Authentication (13 tests)
├── customer-dashboard.spec.js      # Dashboard (12 tests)
├── customer-transactions.spec.js   # Transactions (15 tests)
├── employee-operations.spec.js     # Employee ops (10 tests)
├── bank-account-management.spec.js # Accounts (14 tests)
├── fixtures.js                     # Test fixtures
├── utils/
│   ├── api-client.js
│   ├── helpers.js
│   └── test-data.js
└── README.md
```

## CI/CD

```bash
# Run in CI mode (with retries)
CI=1 npm run test:e2e

# Sharding (split across workers)
npx playwright test --shard=1/4
npx playwright test --shard=2/4
```

## Files Generated

- `test-results/` - Test execution artifacts
- `playwright-report/` - HTML report
- `playwright/.cache/` - Browser cache
- `e2e/screenshots/` - Failure screenshots

## Quick Tips

1. **Run setup first**: Ensure backend, frontend, and fixtures are ready
2. **Use UI mode**: Best for debugging and developing tests
3. **Check selectors**: Use Playwright Inspector to verify selectors
4. **Isolate tests**: Each test should be independent
5. **Use helpers**: Leverage utility functions in `e2e/utils/`

## Resources

- [Full Documentation](./README.md)
- [Setup Guide](./SETUP.md)
- [Implementation Summary](./IMPLEMENTATION_SUMMARY.md)
- [Playwright Docs](https://playwright.dev/)
