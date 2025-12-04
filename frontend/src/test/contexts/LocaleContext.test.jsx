import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { I18nextProvider } from 'react-i18next';
import i18n from 'i18next';
import { LocaleProvider, useLocale } from '../../contexts/LocaleContext';

// Mock i18n instance
const createMockI18n = () => {
  const mockI18n = {
    language: 'pl',
    languages: ['pl', 'en'],
    isInitialized: true,
    changeLanguage: vi.fn((lang) => {
      mockI18n.language = lang;
      return Promise.resolve();
    }),
    getFixedT: vi.fn(() => (key) => key),
    hasLoadedNamespace: vi.fn(() => true),
    loadNamespaces: vi.fn(() => Promise.resolve()),
    use: vi.fn().mockReturnThis(),
    init: vi.fn().mockReturnThis(),
    on: vi.fn(),
    off: vi.fn(),
    options: {
      react: { useSuspense: false }
    },
    store: {
      on: vi.fn(),
    },
  };
  return mockI18n;
};

// Test component that uses LocaleContext
const TestComponent = () => {
  const { locale, setLocale, supportedLocales } = useLocale();

  return (
    <div>
      <div>Current locale: {locale}</div>
      <div>Supported: {supportedLocales.join(', ')}</div>
      <button onClick={() => setLocale('en')}>Set English</button>
      <button onClick={() => setLocale('pl')}>Set Polish</button>
      <button onClick={() => setLocale('fr')}>Set French</button>
    </div>
  );
};

describe('LocaleContext', () => {
  let mockI18n;

  beforeEach(() => {
    mockI18n = createMockI18n();
    vi.clearAllMocks();
    localStorage.clear();
  });

  it('should throw error when useLocale is used outside provider', () => {
    // Suppress console.error for this test
    const spy = vi.spyOn(console, 'error').mockImplementation(() => {});

    expect(() => {
      render(<TestComponent />);
    }).toThrow('useLocale must be used within LocaleProvider');

    spy.mockRestore();
  });

  it('should provide default locale', async () => {
    render(
      <I18nextProvider i18n={mockI18n}>
        <LocaleProvider>
          <TestComponent />
        </LocaleProvider>
      </I18nextProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Current locale: pl')).toBeInTheDocument();
    });
  });

  it('should provide supported locales', async () => {
    render(
      <I18nextProvider i18n={mockI18n}>
        <LocaleProvider>
          <TestComponent />
        </LocaleProvider>
      </I18nextProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Supported: pl, en')).toBeInTheDocument();
    });
  });

  it('should change locale to English', async () => {
    render(
      <I18nextProvider i18n={mockI18n}>
        <LocaleProvider>
          <TestComponent />
        </LocaleProvider>
      </I18nextProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Current locale: pl')).toBeInTheDocument();
    });

    const englishButton = screen.getByRole('button', { name: 'Set English' });
    englishButton.click();

    await waitFor(() => {
      expect(mockI18n.changeLanguage).toHaveBeenCalledWith('en');
    });

    expect(localStorage.getItem('locale')).toBe('en');
  });

  it('should change locale to Polish', async () => {
    mockI18n.language = 'en';

    render(
      <I18nextProvider i18n={mockI18n}>
        <LocaleProvider>
          <TestComponent />
        </LocaleProvider>
      </I18nextProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Current locale: en')).toBeInTheDocument();
    });

    const polishButton = screen.getByRole('button', { name: 'Set Polish' });
    polishButton.click();

    await waitFor(() => {
      expect(mockI18n.changeLanguage).toHaveBeenCalledWith('pl');
    });

    expect(localStorage.getItem('locale')).toBe('pl');
  });

  it('should not change to unsupported locale', async () => {
    render(
      <I18nextProvider i18n={mockI18n}>
        <LocaleProvider>
          <TestComponent />
        </LocaleProvider>
      </I18nextProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Current locale: pl')).toBeInTheDocument();
    });

    const frenchButton = screen.getByRole('button', { name: 'Set French' });
    frenchButton.click();

    // Should not call changeLanguage for unsupported locale
    await waitFor(() => {
      expect(mockI18n.changeLanguage).not.toHaveBeenCalledWith('fr');
    });
  });

  it('should set document language attribute', async () => {
    render(
      <I18nextProvider i18n={mockI18n}>
        <LocaleProvider>
          <TestComponent />
        </LocaleProvider>
      </I18nextProvider>
    );

    await waitFor(() => {
      expect(document.documentElement.lang).toBe('pl');
    });
  });

  it('should not render children when i18n is not initialized', () => {
    const uninitializedI18n = createMockI18n();
    uninitializedI18n.isInitialized = false;

    const { container } = render(
      <I18nextProvider i18n={uninitializedI18n}>
        <LocaleProvider>
          <TestComponent />
        </LocaleProvider>
      </I18nextProvider>
    );

    expect(container.firstChild).toBeNull();
  });
});
