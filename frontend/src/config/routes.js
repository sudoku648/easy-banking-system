/**
 * Localized route paths configuration
 * Each route key maps to locale-specific paths (without leading slash for nested routes)
 */
export const routes = {
  login: {
    en: 'login',
    pl: 'logowanie'
  },
  customer: {
    dashboard: {
      en: 'customer/dashboard',
      pl: 'klient/panel'
    },
    transactionHistory: {
      en: 'customer/transaction-history',
      pl: 'klient/historia-transakcji'
    },
    transfer: {
      en: 'customer/transfer',
      pl: 'klient/przelew'
    },
    changePassword: {
      en: 'customer/change-password',
      pl: 'klient/zmien-haslo'
    },
    blockDebitCard: {
      en: 'customer/block-debit-card',
      pl: 'klient/zablokuj-karte'
    }
  },
  employee: {
    dashboard: {
      en: 'employee/dashboard',
      pl: 'pracownik/panel'
    },
    selectCustomer: {
      en: 'employee/select-customer',
      pl: 'pracownik/wybierz-klienta'
    },
    transactionHistoryByCustomer: {
      en: 'employee/transaction-history/customer/:customerId',
      pl: 'pracownik/historia-transakcji/klient/:customerId'
    },
    transactionHistoryByAccount: {
      en: 'employee/transaction-history/account/:bankAccountId',
      pl: 'pracownik/historia-transakcji/konto/:bankAccountId'
    },
    deposit: {
      en: 'employee/deposit',
      pl: 'pracownik/wplata'
    },
    openAccountNew: {
      en: 'employee/open-account-new',
      pl: 'pracownik/otworz-konto-nowy'
    },
    openAccountExisting: {
      en: 'employee/open-account-existing',
      pl: 'pracownik/otworz-konto-istniejacy'
    },
    closeAccount: {
      en: 'employee/close-account',
      pl: 'pracownik/zamknij-konto'
    },
    issueDebitCard: {
      en: 'employee/issue-debit-card',
      pl: 'pracownik/wydaj-karte'
    },
    blockDebitCard: {
      en: 'employee/block-debit-card',
      pl: 'pracownik/zablokuj-karte'
    },
    changePassword: {
      en: 'employee/change-password',
      pl: 'pracownik/zmien-haslo'
    }
  }
};

/**
 * Get localized path for a route (without locale prefix)
 * @param {string} routeKey - Nested route key (e.g., 'customer.dashboard')
 * @param {string} locale - Current locale
 * @param {object} params - Optional parameters to replace in path
 * @returns {string} Localized path
 */
export const getLocalizedPath = (routeKey, locale = 'pl', params = {}) => {
  const keys = routeKey.split('.');
  let path = routes;

  for (const key of keys) {
    path = path[key];
    if (!path) {
      console.warn(`Route key "${routeKey}" not found`);
      return '';
    }
  }

  let localizedPath = path[locale] || path.pl;

  // Replace params in path
  Object.entries(params).forEach(([key, value]) => {
    localizedPath = localizedPath.replace(`:${key}`, value);
  });

  return localizedPath;
};

/**
 * Get full localized URL with locale prefix
 * @param {string} routeKey - Nested route key
 * @param {string} locale - Current locale
 * @param {object} params - Optional parameters
 * @returns {string} Full localized URL with leading slash
 */
export const getLocalizedUrl = (routeKey, locale = 'pl', params = {}) => {
  const path = getLocalizedPath(routeKey, locale, params);
  return `/${locale}/${path}`;
};

/**
 * Build a map of all route patterns for React Router
 * Maps localized paths to their route keys for reverse lookup
 * @param {string} locale - Locale to build routes for
 * @returns {object} Route patterns mapped to route keys
 */
export const buildRoutePatterns = (locale) => {
  const patterns = {};

  const traverse = (obj, prefix = '') => {
    Object.entries(obj).forEach(([key, value]) => {
      if (typeof value === 'object' && (value.en || value.pl)) {
        // This is a route definition with locale keys
        const routeKey = prefix ? `${prefix}.${key}` : key;
        const path = value[locale] || value.pl;
        patterns[path] = routeKey;
      } else if (typeof value === 'object') {
        // This is a nested object, traverse deeper
        traverse(value, prefix ? `${prefix}.${key}` : key);
      }
    });
  };

  traverse(routes);
  return patterns;
};

/**
 * Find route key from a URL path (handles dynamic parameters)
 * @param {string} path - Current path without locale prefix
 * @param {string} locale - Current locale
 * @returns {string|null} Route key or null if not found
 */
export const findRouteKey = (path, locale) => {
  const patterns = buildRoutePatterns(locale);

  // First try exact match
  if (patterns[path]) {
    return patterns[path];
  }

  // Try pattern matching for routes with parameters
  for (const [pattern, routeKey] of Object.entries(patterns)) {
    if (pattern.includes(':')) {
      // Convert pattern to regex (e.g., "employee/history/:id" -> "employee/history/[^/]+")
      const regexPattern = pattern.replace(/:[^/]+/g, '[^/]+');
      const regex = new RegExp(`^${regexPattern}$`);
      if (regex.test(path)) {
        return routeKey;
      }
    }
  }

  return null;
};
