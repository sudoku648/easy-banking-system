import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
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
