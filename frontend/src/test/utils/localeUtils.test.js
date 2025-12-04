import { describe, it, expect } from 'vitest';
import { getLocalePath, extractLocaleFromPath, removeLocaleFromPath } from '../../utils/localeUtils';

describe('localeUtils', () => {
  describe('getLocalePath', () => {
    it('should generate locale-prefixed path', () => {
      expect(getLocalePath('en', '/dashboard')).toBe('/en/dashboard');
      expect(getLocalePath('pl', '/dashboard')).toBe('/pl/dashboard');
    });

    it('should handle path without leading slash', () => {
      expect(getLocalePath('en', 'dashboard')).toBe('/en/dashboard');
    });

    it('should handle empty path', () => {
      expect(getLocalePath('en', '')).toBe('/en/');
    });

    it('should handle nested paths', () => {
      expect(getLocalePath('en', '/customer/dashboard')).toBe('/en/customer/dashboard');
    });
  });

  describe('extractLocaleFromPath', () => {
    it('should extract locale from path', () => {
      expect(extractLocaleFromPath('/en/dashboard')).toBe('en');
      expect(extractLocaleFromPath('/pl/dashboard')).toBe('pl');
    });

    it('should extract locale from root path', () => {
      expect(extractLocaleFromPath('/en')).toBe('en');
      expect(extractLocaleFromPath('/pl/')).toBe('pl');
    });

    it('should return null for invalid path', () => {
      expect(extractLocaleFromPath('/dashboard')).toBe(null);
      expect(extractLocaleFromPath('/')).toBe(null);
      expect(extractLocaleFromPath('')).toBe(null);
    });

    it('should return null for invalid locale code', () => {
      expect(extractLocaleFromPath('/english/dashboard')).toBe(null);
      expect(extractLocaleFromPath('/123/dashboard')).toBe(null);
    });
  });

  describe('removeLocaleFromPath', () => {
    it('should remove locale prefix from path', () => {
      expect(removeLocaleFromPath('/en/dashboard')).toBe('/dashboard');
      expect(removeLocaleFromPath('/pl/customer/transfer')).toBe('/customer/transfer');
    });

    it('should return root for locale-only path', () => {
      expect(removeLocaleFromPath('/en')).toBe('/');
      expect(removeLocaleFromPath('/pl')).toBe('/');
    });
  });
});
