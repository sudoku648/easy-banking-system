import { Navigate, useParams } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useLocale } from '../contexts/LocaleContext';
import { getLocalizedUrl } from '../config/routes';

const ProtectedRoute = ({ children, role }) => {
  const { user, loading } = useAuth();
  const { locale: urlLocale } = useParams();
  const { locale } = useLocale();
  const currentLocale = urlLocale || locale;

  if (loading) {
    return <div className="loading">Loading...</div>;
  }

  if (!user) {
    return <Navigate to={getLocalizedUrl('login', currentLocale)} replace />;
  }

  if (role && user.role !== role) {
    return <Navigate to={getLocalizedUrl('login', currentLocale)} replace />;
  }

  return children;
};

export default ProtectedRoute;
