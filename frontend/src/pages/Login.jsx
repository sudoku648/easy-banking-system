import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../contexts/AuthContext';
import { useLocale } from '../contexts/LocaleContext';
import { useLocaleNavigate } from '../hooks/useLocaleNavigate';
import { getLocalizedUrl } from '../config/routes';

const Login = () => {
  const { t } = useTranslation();
  const { login, user } = useAuth();
  const { locale, setLocale, supportedLocales } = useLocale();
  const navigate = useLocaleNavigate();
  const [formData, setFormData] = useState({
    username: '',
    password: '',
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // Redirect if already logged in
  useEffect(() => {
    if (user) {
      if (user.role === 'CUSTOMER') {
        navigate('customer.dashboard', { replace: true });
      } else if (user.role === 'EMPLOYEE') {
        navigate('employee.dashboard', { replace: true });
      }
    }
  }, [user, navigate]);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const result = await login(formData);

      // Redirect based on role
      if (result.data.user.role === 'CUSTOMER') {
        navigate('customer.dashboard');
      } else if (result.data.user.role === 'EMPLOYEE') {
        navigate('employee.dashboard');
      }
    } catch (err) {
      setError(err.response?.data?.message || t('login.invalid_credentials'));
    } finally {
      setLoading(false);
    }
  };

  const localeFlags = {
    pl: '🇵🇱',
    en: '🇬🇧'
  };

  const localeNames = {
    pl: 'Polski',
    en: 'English'
  };

  return (
    <>
      {/* Navbar */}
      <nav className="navbar navbar-expand-lg navbar-dark bg-primary">
        <div className="container-fluid">
          <a className="navbar-brand" href="/">
            <i className="bi bi-bank2"></i> {t('app.name')}
          </a>
          <button className="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span className="navbar-toggler-icon"></span>
          </button>
          <div className="collapse navbar-collapse" id="navbarNav">
            <ul className="navbar-nav ms-auto">
              <li className="nav-item">
                <a className="nav-link" href={getLocalizedUrl('login', locale)}>
                  <i className="bi bi-box-arrow-in-right"></i> {t('nav.login')}
                </a>
              </li>
            </ul>
          </div>
        </div>
      </nav>

      {/* Main Content */}
      <main>
        <div className="container mt-4">
          <div className="row justify-content-center">
            <div className="col-md-6 col-lg-5">
              <div className="card shadow-sm mt-5">
                <div className="card-body p-5">
                  {/* Language Switcher */}
                  <div className="text-end mb-3">
                    <div className="btn-group btn-group-sm" role="group">
                      {supportedLocales.map((loc) => (
                        <button
                          key={loc}
                          onClick={() => setLocale(loc)}
                          className={`btn btn-outline-secondary ${locale === loc ? 'active' : ''}`}
                        >
                          {localeFlags[loc]} {localeNames[loc]}
                        </button>
                      ))}
                    </div>
                  </div>

                  <h1 className="text-center mb-4">
                    <i className="bi bi-bank2 text-primary"></i><br />
                    {t('app.name')}
                  </h1>

                  <h2 className="h4 text-center mb-4">{t('login.sign_in')}</h2>

                  {error && (
                    <div className="alert alert-danger">
                      <i className="bi bi-exclamation-triangle"></i> {error}
                    </div>
                  )}

                  <form onSubmit={handleSubmit}>
                    <div className="mb-3">
                      <label htmlFor="username" className="form-label">{t('login.username')}</label>
                      <input
                        type="text"
                        className="form-control"
                        id="username"
                        name="username"
                        value={formData.username}
                        onChange={handleChange}
                        required
                        autoFocus
                        placeholder={t('login.username_placeholder')}
                      />
                    </div>

                    <div className="mb-3">
                      <label htmlFor="password" className="form-label">{t('login.password')}</label>
                      <input
                        type="password"
                        className="form-control"
                        id="password"
                        name="password"
                        value={formData.password}
                        onChange={handleChange}
                        required
                        placeholder={t('login.password_placeholder')}
                      />
                    </div>

                    <div className="d-grid">
                      <button type="submit" className="btn btn-primary btn-lg" disabled={loading}>
                        <i className="bi bi-box-arrow-in-right"></i> {loading ? t('login.signing_in') : t('login.sign_in')}
                      </button>
                    </div>
                  </form>

                  <div className="text-center mt-4">
                    <small className="text-muted">
                      <i className="bi bi-info-circle"></i> {t('login.info')}
                    </small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="mt-5 py-3 bg-light text-center">
        <div className="container">
          <p className="text-muted mb-0">{t('app.copyright')}</p>
        </div>
      </footer>
    </>
  );
};

export default Login;
