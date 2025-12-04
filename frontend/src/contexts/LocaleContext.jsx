import { createContext, useContext, useEffect } from 'react';
import { useTranslation } from 'react-i18next';

const LocaleContext = createContext(null);

const SUPPORTED_LOCALES = ['pl', 'en'];
const DEFAULT_LOCALE = 'pl';

export const LocaleProvider = ({ children }) => {
  const { i18n } = useTranslation();
  const locale = i18n.language || DEFAULT_LOCALE;

  useEffect(() => {
    document.documentElement.lang = locale;
  }, [locale]);

  const setLocale = (newLocale) => {
    if (SUPPORTED_LOCALES.includes(newLocale)) {
      i18n.changeLanguage(newLocale);
      localStorage.setItem('locale', newLocale);
    }
  };

  // Wait for i18n to be initialized
  if (!i18n.isInitialized) {
    return null;
  }

  return (
    <LocaleContext.Provider value={{
      locale,
      setLocale,
      supportedLocales: SUPPORTED_LOCALES,
      defaultLocale: DEFAULT_LOCALE
    }}>
      {children}
    </LocaleContext.Provider>
  );
};

export const useLocale = () => {
  const context = useContext(LocaleContext);
  if (!context) {
    throw new Error('useLocale must be used within LocaleProvider');
  }
  return context;
};
