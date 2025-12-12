import { useState, useEffect } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import api from '../../api/client';
import { useAuth } from '../../contexts/AuthContext';
import { useLocale } from '../../contexts/LocaleContext';
import { getLocalizedUrl } from '../../config/routes';
import Layout from '../../components/Layout';
import Card from '../../components/Card';
import DashboardCard from '../../components/DashboardCard';

const CustomerDashboard = () => {
  const { t } = useTranslation();
  const { user } = useAuth();
  const { locale: urlLocale } = useParams();
  const { locale } = useLocale();
  const currentLocale = urlLocale || locale;
  const [accounts, setAccounts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchAccounts();
  }, []);

  const fetchAccounts = async () => {
    try {
      const response = await api.get('/customer/accounts');
      setAccounts(response.data.data.accounts || []);
    } catch (err) {
      setError('Failed to load accounts');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <Layout>
        <div className="loading">Loading...</div>
      </Layout>
    );
  }

  return (
    <Layout>
      {error && <div className="alert alert-danger alert-dismissible fade show">
        <i className="bi bi-exclamation-triangle"></i> {error}
        <button type="button" className="btn-close" onClick={() => setError('')}></button>
      </div>}

      {/* Dashboard Header */}
      <div className="row">
        <div className="col-12">
          <h1 className="mb-4">
            <i className="bi bi-speedometer2"></i> {t('dashboard.customer_title')}
          </h1>
          <p className="lead">{t('dashboard.welcome_message', { name: `${user?.firstName} ${user?.lastName}` })}</p>
        </div>
      </div>

      {/* Accounts Table */}
      <div className="row mt-4">
        <div className="col-12">
          <Card title={<><i className="bi bi-wallet2"></i> {t('dashboard.your_accounts')}</>}>
            {accounts.length === 0 ? (
              <div className="alert alert-info">
                <i className="bi bi-info-circle"></i> {t('dashboard.no_accounts')}
              </div>
            ) : (
              <div className="table-responsive">
                <table className="table table-hover">
                  <thead>
                    <tr>
                      <th>{t('bank_account.iban')}</th>
                      <th className="text-end">{t('bank_account.balance')}</th>
                      <th className="text-end">{t('bank_account.blocked_amount')}</th>
                      <th className="text-end">{t('bank_account.available_balance')}</th>
                      <th>{t('bank_account.currency')}</th>
                      <th className="text-center">{t('common.status')}</th>
                      <th className="text-center">{t('common.actions')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {accounts.map((account) => (
                      <tr key={account.id}>
                        <td>
                          <code>{account.iban}</code>
                        </td>
                        <td className="text-end">
                          <strong>{(account.balance / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                        </td>
                        <td className="text-end">
                          {account.blockedAmount > 0 ? (
                            <span className="text-warning">
                              <i className="bi bi-lock"></i> {(account.blockedAmount / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </span>
                          ) : (
                            <span className="text-muted">0.00</span>
                          )}
                        </td>
                        <td className="text-end">
                          <strong className="text-success">{(account.availableBalance / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                        </td>
                        <td>
                          <span className="badge bg-secondary">{account.currency}</span>
                        </td>
                        <td className="text-center">
                          {account.isActive ? (
                            <span className="badge bg-success">{t('common.active')}</span>
                          ) : (
                            <span className="badge bg-danger">{t('common.closed')}</span>
                          )}
                        </td>
                        <td className="text-center">
                          {account.isActive ? (
                            <Link to={`${getLocalizedUrl('customer.transfer', currentLocale)}?accountId=${account.id}`} className="btn btn-sm btn-primary">
                              <i className="bi bi-arrow-left-right"></i> {t('transaction.transfer')}
                            </Link>
                          ) : (
                            <button className="btn btn-sm btn-secondary" disabled>
                              {t('common.account_closed')}
                            </button>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </Card>
        </div>
      </div>

      {/* Action Cards */}
      <div className="row mt-4">
        <div className="col-md-6 mb-3">
          <DashboardCard
            icon="arrow-left-right"
            iconColor="primary"
            title={t('dashboard.make_transfer')}
            description={t('dashboard.make_transfer_desc')}
            buttonText={t('dashboard.transfer_money')}
            buttonColor="primary"
            routeKey="customer.transfer"
          />
        </div>

        <div className="col-md-6 mb-3">
          <DashboardCard
            icon="clock-history"
            iconColor="info"
            title={t('dashboard.transaction_history')}
            description={t('dashboard.transaction_history_desc')}
            buttonText={t('dashboard.view_history')}
            buttonColor="info"
            routeKey="customer.transactionHistory"
          />
        </div>
      </div>

      <div className="row mt-4">
        <div className="col-md-6 mb-3">
          <DashboardCard
            icon="slash-circle"
            iconColor="danger"
            title={t('bank_account.block_my_debit_card')}
            description={t('bank_account.block_my_debit_card_description')}
            buttonText={t('bank_account.block_card')}
            buttonColor="danger"
            routeKey="customer.blockDebitCard"
          />
        </div>

        <div className="col-md-6 mb-3">
          <DashboardCard
            icon="key"
            iconColor="warning"
            title={t('user.change_password')}
            description={t('user.change_password_description')}
            buttonText={t('user.change_password')}
            buttonColor="warning"
            routeKey="customer.changePassword"
          />
        </div>
      </div>
    </Layout>
  );
};

export default CustomerDashboard;
