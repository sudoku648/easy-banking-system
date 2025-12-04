import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../../api/client';
import Layout from '../../components/Layout';
import FormCard from '../../components/FormCard';
import FormField from '../../components/FormField';

const DepositMoney = () => {
  const { t } = useTranslation();
  const [accounts, setAccounts] = useState([]);
  const [formData, setFormData] = useState({
    bankAccountId: '',
    amount: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    fetchAccounts();
  }, []);

  const fetchAccounts = async () => {
    try {
      const response = await api.get('/employee/customers');
      const customers = response.data.data.customers || [];
      const allAccounts = [];
      for (const customer of customers) {
        const accountsResponse = await api.get(`/employee/customers/${customer.id}/accounts`);
        const customerAccounts = accountsResponse.data.data.accounts || [];
        allAccounts.push(...customerAccounts.map(acc => ({
          ...acc,
          customerName: `${customer.firstName} ${customer.lastName}`
        })));
      }
      setAccounts(allAccounts);
    } catch (err) {
      console.error('Failed to load accounts', err);
    }
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const response = await api.post('/employee/deposit', formData);
      setSuccess(response.data.message || t('transaction.deposit_successful'));
      setFormData({
        bankAccountId: '',
        amount: '',
      });
    } catch (err) {
      setError(err.response?.data?.message || t('common.error_occurred'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <Layout>
      <FormCard
        title={t('transaction.deposit')}
        icon="cash-coin"
        color="warning"
        cancelRoute="employee.dashboard"
        submitText={t('transaction.deposit')}
        onSubmit={handleSubmit}
        loading={loading}
        alert={
          <>
            <div className="alert alert-info">
              <i className="bi bi-info-circle"></i> {t('transaction.cash_deposit')}
            </div>
            {error && <div className="alert alert-danger"><i className="bi bi-exclamation-triangle"></i> {error}</div>}
            {success && <div className="alert alert-success"><i className="bi bi-check-circle"></i> {success}</div>}
          </>
        }
      >
        <div className="mb-3">
          <FormField
            label={t('bank_account.bank_account')}
            type="select"
            name="bankAccountId"
            value={formData.bankAccountId}
            onChange={handleChange}
            options={[
              { value: '', label: t('transaction.placeholder_select_bank_account') },
              ...accounts.filter(acc => acc.isActive).map(acc => ({
                value: acc.id,
                label: `${acc.iban} - ${acc.customerName} (${acc.currency})`
              }))
            ]}
            required
          />
        </div>

        <div className="mb-3">
          <FormField
            label={t('transaction.amount')}
            type="number"
            name="amount"
            value={formData.amount}
            onChange={handleChange}
            placeholder={t('transaction.placeholder_amount')}
            required
          />
        </div>
      </FormCard>
    </Layout>
  );
};

export default DepositMoney;
