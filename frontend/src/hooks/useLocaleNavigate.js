import { useNavigate, useParams } from 'react-router-dom';
import { useLocale } from '../contexts/LocaleContext';
import { getLocalizedUrl } from '../config/routes';

/**
 * Custom hook for locale-aware navigation with localized paths
 * Supports both route keys and direct paths
 */
export const useLocaleNavigate = () => {
  const navigate = useNavigate();
  const { locale: urlLocale } = useParams();
  const { locale } = useLocale();

  const localeNavigate = (pathOrKey, options = {}) => {
    const currentLocale = urlLocale || locale;

    // If it looks like a route key (no slashes), use localized routing
    if (!pathOrKey.includes('/')) {
      const localePath = getLocalizedUrl(pathOrKey, currentLocale, options.params || {});
      navigate(localePath, options);
    } else {
      // Direct path - just prepend locale
      const localePath = pathOrKey.startsWith('/') ? `/${currentLocale}${pathOrKey}` : pathOrKey;
      navigate(localePath, options);
    }
  };

  return localeNavigate;
};
