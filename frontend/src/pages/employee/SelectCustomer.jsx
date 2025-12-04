import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../../api/client';
import { useLocaleNavigate } from '../../hooks/useLocaleNavigate';
import Layout from '../../components/Layout';
import Card from '../../components/Card';

const SelectCustomer = () => {
  const { t } = useTranslation();
  const navigate = useLocaleNavigate();

  const [customers, setCustomers] = useState([]);
  const [accounts, setAccounts] = useState([]);
  const [allAccounts, setAllAccounts] = useState([]);
  const [customerId, setCustomerId] = useState('');
  const [bankAccountId, setBankAccountId] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchData();
  }, []);

  // Filter accounts when customer changes
  useEffect(() => {
    if (customerId) {
      const customerAccounts = allAccounts.filter(
        acc => acc.customerId === customerId
      );
      setAccounts(customerAccounts);

      // Auto-select account if customer has only one
      if (customerAccounts.length === 1) {
        setBankAccountId(customerAccounts[0].id);
      } else {
        setBankAccountId('');
      }
    } else {
      setAccounts(allAccounts);
      setBankAccountId('');
    }
  }, [customerId, allAccounts]);

  // Auto-fill customer when account is selected
  useEffect(() => {
    if (bankAccountId) {
      const account = allAccounts.find(acc => acc.id === bankAccountId);
      if (account && account.customerId !== customerId) {
        setCustomerId(account.customerId);
      }
    }
  }, [bankAccountId, allAccounts]);

  const fetchData = async () => {
    setLoading(true);
    try {
      const [customersRes, accountsRes] = await Promise.all([
        api.get('/employee/customers'),
        api.get('/employee/active-accounts'),
      ]);

      setCustomers(customersRes.data.data.customers || []);
      setAllAccounts(accountsRes.data.data || []);
      setAccounts(accountsRes.data.data || []);
    } catch (err) {
      setError(err.response?.data?.message || t('common.error_loading_data'));
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    // Priority: if bank account is selected, use it; otherwise use customer
    if (bankAccountId) {
      navigate('employee.transactionHistoryByAccount', { params: { bankAccountId } });
    } else if (customerId) {
      navigate('employee.transactionHistoryByCustomer', { params: { customerId } });
    } else {
      setError(t('transaction.select_customer_or_account_error'));
    }
  };

  if (loading) {
    return (
      <Layout>
        <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">{t('common.loading')}</span>
          </div>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="row">
        <div className="col-md-8 offset-md-2">
          <div className="card shadow-sm">
            <div className="card-header bg-info text-white">
              <h2 className="mb-0">
                <i className="bi bi-clock-history"></i> {t('transaction.select_customer_for_history')}
              </h2>
            </div>
            <div className="card-body">
              <div className="alert alert-info">
                <i className="bi bi-info-circle"></i> {t('transaction.select_customer_or_account')}
              </div>

              {error && <div className="alert alert-danger">{error}</div>}

              <form onSubmit={handleSubmit}>
                <div className="mb-3">
                  <label htmlFor="customerId" className="form-label">
                    {t('transaction.select_customer')}
                  </label>
                  <select
                    id="customerId"
                    className="form-select"
                    value={customerId}
                    onChange={(e) => setCustomerId(e.target.value)}
                  >
                    <option value="">{t('transaction.placeholder_select_customer')}</option>
                    {customers.map((customer) => (
                      <option key={customer.id} value={customer.id}>
                        {customer.fullName} ({customer.username})
                      </option>
                    ))}
                  </select>
                  <small className="form-text text-muted">
                    {t('transaction.help_select_customer_for_history')}
                  </small>
                </div>

                <div className="mb-3">
                  <label htmlFor="bankAccountId" className="form-label">
                    {t('transaction.select_bank_account')}
                  </label>
                  <select
                    id="bankAccountId"
                    className="form-select"
                    value={bankAccountId}
                    onChange={(e) => setBankAccountId(e.target.value)}
                  >
                    <option value="">{t('transaction.placeholder_select_bank_account')}</option>
                    {accounts.map((account) => (
                      <option key={account.id} value={account.id}>
                        {account.iban} ({(account.balance / 100).toFixed(2)} {account.currency})
                      </option>
                    ))}
                  </select>
                  <small className="form-text text-muted">
                    {t('transaction.help_select_account_for_history')}
                  </small>
                </div>

                <div className="d-flex justify-content-between">
                  <button
                    type="button"
                    className="btn btn-secondary"
                    onClick={() => navigate('employee.dashboard')}
                  >
                    <i className="bi bi-arrow-left"></i> {t('common.cancel')}
                  </button>
                  <button type="submit" className="btn btn-info">
                    <i className="bi bi-check-circle"></i> {t('transaction.view_history')}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default SelectCustomer;
