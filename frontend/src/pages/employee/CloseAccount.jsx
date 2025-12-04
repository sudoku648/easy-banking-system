import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../../api/client';
import Layout from '../../components/Layout';
import FormCard from '../../components/FormCard';
import FormField from '../../components/FormField';

const CloseAccount = () => {
  const { t } = useTranslation();
  const [accounts, setAccounts] = useState([]);
  const [formData, setFormData] = useState({
    bankAccountId: '',
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
      const response = await api.post('/employee/close-account', formData);
      setSuccess(response.data.message || t('bank_account.account_closed_successfully'));
      setFormData({ bankAccountId: '' });
      fetchAccounts(); // Refresh list
    } catch (err) {
      setError(err.response?.data?.message || t('common.error_occurred'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <Layout>
      <FormCard
        title={t('bank_account.close')}
        icon="x-circle"
        color="danger"
        cancelRoute="employee.dashboard"
        submitText={t('bank_account.close')}
        submitIcon="x-circle"
        onSubmit={handleSubmit}
        loading={loading}
        alert={
          <>
            <div className="alert alert-warning">
              <i className="bi bi-exclamation-triangle"></i>
              <strong> {t('flash.warning')}:</strong> {t('bank_account.confirm_close')}
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
            helpText={t('bank_account.help_select_account_to_close')}
            options={[
              { value: '', label: t('bank_account.placeholder_select_account_to_close') },
              ...accounts.filter(acc => acc.isActive).map(acc => ({
                value: acc.id,
                label: `${acc.iban} - ${acc.customerName} (${(acc.balance / 100).toFixed(2)} ${acc.currency})`
              }))
            ]}
            required
          />
        </div>
      </FormCard>
    </Layout>
  );
};

export default CloseAccount;
