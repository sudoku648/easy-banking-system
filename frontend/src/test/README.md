# Frontend Unit Testing

This directory contains unit tests for the React frontend application using **Vitest** and **React Testing Library**.

## Test Structure

```
frontend/src/test/
├── setup.js                 # Global test configuration
├── components/              # Component tests
│   ├── Card.test.jsx
│   └── FormField.test.jsx
├── contexts/                # Context tests
│   ├── AuthContext.test.jsx
│   └── LocaleContext.test.jsx
├── hooks/                   # Hook tests
│   └── useLocaleNavigate.test.jsx
└── utils/                   # Utility function tests
    └── localeUtils.test.js
```

## Running Tests

### Run all tests
```bash
npm test
```

### Run tests in watch mode
```bash
npm test
```

### Run tests with UI
```bash
npm run test:ui
```

### Run tests with coverage
```bash
npm run test:coverage
```

## Test Configuration

- **Framework**: Vitest 1.6.0 (compatible with Node 18)
- **Testing Library**: @testing-library/react 16.3.0
- **DOM Environment**: jsdom 24.1.0
- **Configuration**: `vitest.config.js`

## Writing Tests

### Component Tests

Test React components by rendering them and asserting on the DOM output:

```jsx
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import MyComponent from '../../components/MyComponent';

describe('MyComponent', () => {
  it('should render correctly', () => {
    render(<MyComponent title="Test" />);
    expect(screen.getByText('Test')).toBeInTheDocument();
  });
});
```

### Context Tests

Test React contexts by wrapping test components with providers:

```jsx
import { render, screen } from '@testing-library/react';
import { MyProvider, useMyContext } from '../../contexts/MyContext';

const TestComponent = () => {
  const { value } = useMyContext();
  return <div>{value}</div>;
};

it('should provide context value', () => {
  render(
    <MyProvider>
      <TestComponent />
    </MyProvider>
  );
  expect(screen.getByText('expected value')).toBeInTheDocument();
});
```

### Hook Tests

Test custom hooks using `renderHook`:

```jsx
import { renderHook } from '@testing-library/react';
import { useMyHook } from '../../hooks/useMyHook';

it('should return correct value', () => {
  const { result } = renderHook(() => useMyHook());
  expect(result.current.value).toBe('expected');
});
```

### Utility Tests

Test pure utility functions directly:

```jsx
import { describe, it, expect } from 'vitest';
import { myUtilFunction } from '../../utils/myUtils';

describe('myUtilFunction', () => {
  it('should process input correctly', () => {
    expect(myUtilFunction('input')).toBe('output');
  });
});
```

## Mocking

### Mock modules
```jsx
vi.mock('../../api/client', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));
```

### Mock functions
```jsx
const mockCallback = vi.fn();
```

### Clear mocks
```jsx
beforeEach(() => {
  vi.clearAllMocks();
});
```

## Best Practices

1. **Test behavior, not implementation**: Focus on what the component does, not how it does it
2. **Use semantic queries**: Prefer `getByRole`, `getByLabelText` over `getByTestId`
3. **Test user interactions**: Use `@testing-library/user-event` for realistic interactions
4. **Keep tests simple**: One test should verify one behavior
5. **Use descriptive test names**: Test names should clearly describe what is being tested
6. **Clean up after tests**: Use `cleanup()` to unmount components (automatically done in setup.js)

## Coverage

Run `npm run test:coverage` to generate coverage reports. Coverage reports are generated in:
- **Console**: Text format
- **HTML**: `coverage/` directory
- **JSON**: `coverage/coverage-final.json`

## Troubleshooting

### Tests fail with "Cannot read properties of undefined"
- Ensure all required props are provided
- Check that mocked functions/modules match the expected interface

### React warnings in tests
- Wrap state updates in `act()` if warnings appear
- Ensure async operations complete with `waitFor()`

### Module import errors
- Check file extensions (.js vs .jsx)
- Verify mock paths match actual file paths
- Ensure ES modules are imported correctly in tests

## Additional Resources

- [Vitest Documentation](https://vitest.dev/)
- [React Testing Library](https://testing-library.com/react)
- [Testing Best Practices](https://kentcdodds.com/blog/common-mistakes-with-react-testing-library)
