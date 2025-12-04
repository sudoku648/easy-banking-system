import { useTranslation } from 'react-i18next';
import Header from './Header';

const Layout = ({ children }) => {
  const { t } = useTranslation();
  return (
    <>
      <Header />
      <main>
        <div className="container mt-4">
          {children}
        </div>
      </main>
      <footer className="mt-5 py-3 bg-light text-center">
        <div className="container">
          <p className="text-muted mb-0">{t('app.copyright')}</p>
        </div>
      </footer>
    </>
  );
};

export default Layout;
