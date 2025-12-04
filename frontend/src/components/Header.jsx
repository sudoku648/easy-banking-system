import { Link, useParams, useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../contexts/AuthContext';
import { useLocale } from '../contexts/LocaleContext';
import { useLocaleNavigate } from '../hooks/useLocaleNavigate';
import { getLocalizedUrl, routes, findRouteKey } from '../config/routes';

const Header = () => {
  const { t } = useTranslation();
  const { user, logout } = useAuth();
  const { locale, setLocale, supportedLocales } = useLocale();
  const navigate = useLocaleNavigate();
  const { locale: urlLocale } = useParams();
  const location = useLocation();
  const currentLocale = urlLocale || locale;

  const handleLogout = async () => {
    await logout();
    navigate('login');
  };

  const handleLocaleChange = (newLocale) => {
    setLocale(newLocale);
    
    // Find current route key from current path
    const pathWithoutLocale = location.pathname.replace(/^\/[^/]+\//, ''); // Remove /locale/ prefix
    const matchedRouteKey = findRouteKey(pathWithoutLocale, currentLocale);
    
    if (matchedRouteKey) {
      // Navigate to the same route in new locale
      const newUrl = getLocalizedUrl(matchedRouteKey, newLocale);
      window.location.href = newUrl;
    } else {
      // Fallback to login if route not found
      window.location.href = `/${newLocale}/${routes.login[newLocale]}`;
    }
  };

  const isCustomer = user?.role === 'CUSTOMER';
  const isEmployee = user?.role === 'EMPLOYEE';

  const localeFlags = {
    pl: '🇵🇱',
    en: '🇬🇧'
  };

  return (
    <nav className="navbar navbar-expand-lg navbar-dark bg-primary">
      <div className="container-fluid">
        <Link className="navbar-brand" to={
          isCustomer ? getLocalizedUrl('customer.dashboard', currentLocale) : 
          isEmployee ? getLocalizedUrl('employee.dashboard', currentLocale) : 
          getLocalizedUrl('login', currentLocale)
        }>
          <i className="bi bi-bank2"></i> {t('app.name')}
        </Link>
        <button className="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span className="navbar-toggler-icon"></span>
        </button>
        <div className="collapse navbar-collapse" id="navbarNav">
          <ul className="navbar-nav ms-auto">
            {isEmployee && (
              <>
                <li className="nav-item">
                  <Link className="nav-link" to={getLocalizedUrl('employee.dashboard', currentLocale)}>
                    <i className="bi bi-speedometer2"></i> {t('nav.dashboard')}
                  </Link>
                </li>
                <li className="nav-item">
                  <Link className="nav-link" to={getLocalizedUrl('employee.openAccountNew', currentLocale)}>
                    <i className="bi bi-person-plus"></i> {t('nav.new_account')}
                  </Link>
                </li>
              </>
            )}
            {isCustomer && (
              <>
                <li className="nav-item">
                  <Link className="nav-link" to={getLocalizedUrl('customer.dashboard', currentLocale)}>
                    <i className="bi bi-speedometer2"></i> {t('nav.dashboard')}
                  </Link>
                </li>
                <li className="nav-item">
                  <Link className="nav-link" to={getLocalizedUrl('customer.transfer', currentLocale)}>
                    <i className="bi bi-arrow-left-right"></i> {t('nav.transfer')}
                  </Link>
                </li>
                <li className="nav-item">
                  <Link className="nav-link" to={getLocalizedUrl('customer.transactionHistory', currentLocale)}>
                    <i className="bi bi-clock-history"></i> {t('nav.history')}
                  </Link>
                </li>
              </>
            )}
            {user ? (
              <>
                <li className="nav-item dropdown">
                  <a className="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    {localeFlags[locale]}
                  </a>
                  <ul className="dropdown-menu dropdown-menu-end">
                    {supportedLocales.map((loc) => (
                      <li key={loc}>
                        <button className="dropdown-item" onClick={() => handleLocaleChange(loc)}>
                          {localeFlags[loc]} {loc === 'en' ? 'English' : 'Polski'}
                        </button>
                      </li>
                    ))}
                  </ul>
                </li>
                <li className="nav-item">
                  <button className="nav-link btn btn-link" onClick={handleLogout}>
                    <i className="bi bi-box-arrow-right"></i> {t('nav.logout')}
                  </button>
                </li>
              </>
            ) : (
              <li className="nav-item">
                <Link className="nav-link" to={getLocalizedUrl('login', currentLocale)}>
                  <i className="bi bi-box-arrow-in-right"></i> {t('nav.login')}
                </Link>
              </li>
            )}
          </ul>
        </div>
      </div>
    </nav>
  );
};

export default Header;
