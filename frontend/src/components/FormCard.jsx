import { Link, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useLocale } from '../contexts/LocaleContext';
import { getLocalizedUrl } from '../config/routes';

const FormCard = ({
  title,
  icon,
  color = 'primary',
  description,
  alert,
  children,
  onSubmit,
  cancelRoute,
  submitText,
  submitIcon = 'check-circle',
  loading = false
}) => {
  const { t } = useTranslation();
  const { locale: urlLocale } = useParams();
  const { locale } = useLocale();
  const currentLocale = urlLocale || locale;

  // Support both route keys (e.g., 'employee.dashboard') and full paths
  const cancelUrl = cancelRoute?.includes('/')
    ? cancelRoute
    : getLocalizedUrl(cancelRoute, currentLocale);

  return (
    <div className="row">
      <div className="col-md-8 offset-md-2">
        <div className="card shadow-sm">
          <div className={`card-header bg-${color} text-white`}>
            <h2 className="mb-0">
              <i className={`bi bi-${icon}`}></i> {title}
            </h2>
          </div>
          <div className="card-body">
            {description && (
              <p className="text-muted">{description}</p>
            )}

            {alert && alert}

            <form onSubmit={onSubmit}>
              {children}

              <div className="d-flex justify-content-between">
                <Link to={cancelUrl} className="btn btn-secondary">
                  <i className="bi bi-arrow-left"></i> {t('common.cancel')}
                </Link>
                <button type="submit" className={`btn btn-${color}`} disabled={loading}>
                  <i className={`bi bi-${submitIcon}`}></i> {loading ? t('common.processing') : submitText}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
};

export default FormCard;
