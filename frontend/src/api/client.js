import axios from 'axios';

// Get API URL from environment variable
// In development: http://localhost:8080
// In production: empty string (same origin)
const apiUrl = import.meta.env.VITE_API_URL || '';
const baseURL = apiUrl ? `${apiUrl}/api/frontend` : '/api/frontend';

const api = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
});

// Request interceptor for adding locale
api.interceptors.request.use((config) => {
  const locale = localStorage.getItem('locale') || 'pl';
  config.headers['Accept-Language'] = locale;
  return config;
});

// Response interceptor for handling errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Only redirect on 401 for non-auth endpoints
    if (error.response?.status === 401 && !error.config.url.includes('/auth/')) {
      // Redirect to localized login page
      const locale = localStorage.getItem('locale') || 'pl';
      const loginPaths = { en: 'login', pl: 'logowanie' };
      window.location.href = `/${locale}/${loginPaths[locale]}`;
    }
    return Promise.reject(error);
  }
);

export default api;
