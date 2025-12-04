import { useEffect } from 'react';
import { useParams, Navigate, Outlet } from 'react-router-dom';
import { useLocale } from '../contexts/LocaleContext';

/**
 * Wrapper component that handles locale from URL path
 * Syncs URL locale with i18n locale
 */
const LocaleRoute = ({ children }) => {
  const { locale: urlLocale } = useParams();
  const { locale, setLocale, supportedLocales } = useLocale();

  // If invalid locale, redirect to fallback
  if (urlLocale && !supportedLocales.includes(urlLocale)) {
    return <Navigate to="/pl/logowanie" replace />;
  }

  // Sync URL locale with i18n locale
  useEffect(() => {
    if (urlLocale && urlLocale !== locale) {
      setLocale(urlLocale);
    }
  }, [urlLocale, locale, setLocale]);

  // If children are provided (for wrapping individual components), render them
  // Otherwise, render Outlet for nested routes
  return children || <Outlet />;
};

export default LocaleRoute;
