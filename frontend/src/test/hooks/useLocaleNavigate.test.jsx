import { describe, it, expect, vi, beforeEach } from 'vitest';
import { renderHook } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { useLocaleNavigate } from '../../hooks/useLocaleNavigate';

// Mock the dependencies
const mockNavigate = vi.fn();
const mockUseParams = vi.fn(() => ({ locale: 'pl' }));

vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom');
  return {
    ...actual,
    useNavigate: () => mockNavigate,
    useParams: () => mockUseParams(),
  };
});

// Mock LocaleContext
vi.mock('../../contexts/LocaleContext', () => ({
  useLocale: () => ({ locale: 'pl' }),
}));

// Mock routes config
vi.mock('../../config/routes', () => ({
  getLocalizedUrl: vi.fn((key, locale) => `/${locale}/${key}`),
}));

const wrapper = ({ children }) => <BrowserRouter>{children}</BrowserRouter>;

describe('useLocaleNavigate', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseParams.mockReturnValue({ locale: 'pl' });
  });

  it('should navigate with locale prefix for direct paths', () => {
    const { result } = renderHook(() => useLocaleNavigate(), { wrapper });

    result.current('/dashboard');

    expect(mockNavigate).toHaveBeenCalledWith('/pl/dashboard', {});
  });

  it('should navigate with English locale when URL param is en', () => {
    mockUseParams.mockReturnValue({ locale: 'en' });
    const { result } = renderHook(() => useLocaleNavigate(), { wrapper });

    result.current('/dashboard');

    expect(mockNavigate).toHaveBeenCalledWith('/en/dashboard', {});
  });

  it('should pass options to navigate', () => {
    const { result } = renderHook(() => useLocaleNavigate(), { wrapper });

    const options = { replace: true, state: { from: 'home' } };

    result.current('/dashboard', options);

    expect(mockNavigate).toHaveBeenCalledWith('/pl/dashboard', options);
  });
});
