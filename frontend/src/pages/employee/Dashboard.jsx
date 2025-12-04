import { useTranslation } from 'react-i18next';
import Layout from '../../components/Layout';
import DashboardCard from '../../components/DashboardCard';
import { useAuth } from '../../contexts/AuthContext';

const EmployeeDashboard = () => {
  const { t } = useTranslation();
  const { user } = useAuth();

  return (
    <Layout>
      <div className="row">
        <div className="col-12">
          <h1 className="mb-4">
            <i className="bi bi-speedometer2"></i> {t('dashboard.employee_title')}
          </h1>
          <p className="lead">
            {t('dashboard.welcome_message', { name: `${user?.firstName} ${user?.lastName}` })}
          </p>
        </div>
      </div>

      <div className="row mt-4">
        <div className="col-md-4 mb-3">
          <DashboardCard
            icon="person-plus"
            iconColor="primary"
            title={t('bank_account.open_new')}
            description={t('bank_account.open_for_new_customer')}
            buttonText={t('bank_account.new_customer_info')}
            buttonColor="primary"
            routeKey="employee.openAccountNew"
          />
        </div>

        <div className="col-md-4 mb-3">
          <DashboardCard
            icon="person-check"
            iconColor="success"
            title={t('bank_account.open_for_existing_customer')}
            description={t('bank_account.open_for_existing_customer')}
            buttonText={t('bank_account.select_customer')}
            buttonColor="success"
            routeKey="employee.openAccountExisting"
          />
        </div>

        <div className="col-md-4 mb-3">
          <DashboardCard
            icon="x-circle"
            iconColor="danger"
            title={t('bank_account.close')}
            description={t('bank_account.close')}
            buttonText={t('bank_account.close')}
            buttonColor="danger"
            routeKey="employee.closeAccount"
          />
        </div>

        <div className="col-md-4 mb-3">
          <DashboardCard
            icon="cash-coin"
            iconColor="warning"
            title={t('transaction.deposit')}
            description={t('transaction.cash_deposit')}
            buttonText={t('transaction.deposit')}
            buttonColor="warning"
            routeKey="employee.deposit"
          />
        </div>

        <div className="col-md-4 mb-3">
          <DashboardCard
            icon="credit-card"
            iconColor="primary"
            title={t('bank_account.issue_debit_card')}
            description={t('bank_account.issue_debit_card_description')}
            buttonText={t('bank_account.issue_card')}
            buttonColor="primary"
            routeKey="employee.issueDebitCard"
          />
        </div>

        <div className="col-md-4 mb-3">
          <DashboardCard
            icon="slash-circle"
            iconColor="danger"
            title={t('bank_account.block_debit_card')}
            description={t('bank_account.block_debit_card_description')}
            buttonText={t('bank_account.block_card')}
            buttonColor="danger"
            routeKey="employee.blockDebitCard"
          />
        </div>

        <div className="col-md-4 mb-3">
          <DashboardCard
            icon="clock-history"
            iconColor="info"
            title={t('transaction.customer_history')}
            description={t('transaction.view_customer_history')}
            buttonText={t('transaction.view_history')}
            buttonColor="info"
            routeKey="employee.selectCustomer"
          />
        </div>

        <div className="col-md-4 mb-3">
          <DashboardCard
            icon="key"
            iconColor="secondary"
            title={t('user.change_password')}
            description={t('user.change_password_description')}
            buttonText={t('user.change_password')}
            buttonColor="secondary"
            routeKey="employee.changePassword"
          />
        </div>
      </div>

      <div className="row mt-4">
        <div className="col-12">
          <div className="card shadow-sm">
            <div className="card-body">
              <h5 className="card-title">
                <i className="bi bi-info-circle text-info"></i> {t('dashboard.quick_info')}
              </h5>
              <ul className="mb-0">
                <li>{t('dashboard.quick_info.currency')}</li>
                <li>{t('dashboard.quick_info.iban')}</li>
                <li>{t('dashboard.quick_info.close')}</li>
                <li>{t('dashboard.quick_info.history')}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default EmployeeDashboard;
