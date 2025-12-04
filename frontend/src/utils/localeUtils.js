/**
 * Generates a locale-prefixed path
 * @param {string} locale - The locale code (e.g., 'en', 'pl')
 * @param {string} path - The path without locale prefix
 * @returns {string} The locale-prefixed path
 */
export const getLocalePath = (locale, path) => {
  const cleanPath = path.startsWith('/') ? path : `/${path}`;
  return `/${locale}${cleanPath}`;
};

/**
 * Extracts the locale from a path
 * @param {string} path - The full path including locale
 * @returns {string|null} The locale code or null if not found
 */
export const extractLocaleFromPath = (path) => {
  const match = path.match(/^\/([a-z]{2})(\/|$)/);
  return match ? match[1] : null;
};

/**
 * Removes the locale prefix from a path
 * @param {string} path - The full path including locale
 * @returns {string} The path without locale prefix
 */
export const removeLocaleFromPath = (path) => {
  return path.replace(/^\/[a-z]{2}/, '') || '/';
};
