import { Link, useParams } from 'react-router-dom';
import { useLocale } from '../contexts/LocaleContext';
import { getLocalizedUrl } from '../config/routes';

const DashboardCard = ({ icon, iconColor, title, description, buttonText, buttonColor, routeKey }) => {
  const { locale: urlLocale } = useParams();
  const { locale } = useLocale();
  const currentLocale = urlLocale || locale;

  return (
    <div className="card h-100 shadow-sm">
      <div className="card-body text-center">
        <i className={`bi bi-${icon} display-4 text-${iconColor} mb-3`}></i>
        <h5 className="card-title">{title}</h5>
        <p className="card-text">{description}</p>
        <Link to={getLocalizedUrl(routeKey, currentLocale)} className={`btn btn-${buttonColor}`}>
          <i className={`bi bi-${icon}`}></i> {buttonText}
        </Link>
      </div>
    </div>
  );
};

export default DashboardCard;
