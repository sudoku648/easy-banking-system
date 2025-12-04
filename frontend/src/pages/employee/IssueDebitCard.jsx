import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../../api/client';
import Layout from '../../components/Layout';
import FormCard from '../../components/FormCard';
import FormField from '../../components/FormField';

const IssueDebitCard = () => {
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
      const response = await api.post('/employee/issue-debit-card', formData);
      setSuccess(response.data.message || t('bank_account.card_issued_successfully'));
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
        title={t('bank_account.issue_debit_card')}
        icon="credit-card"
        color="primary"
        description={t('bank_account.issue_debit_card_description')}
        cancelRoute="employee.dashboard"
        submitText={t('bank_account.issue_card')}
        submitIcon="check-circle"
        onSubmit={handleSubmit}
        loading={loading}
        alert={
          <>
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
            helpText={t('bank_account.help_select_account_for_card')}
            options={[
              { value: '', label: t('bank_account.placeholder_select_account_for_card') },
              ...accounts.filter(acc => acc.isActive && !acc.hasDebitCard).map(acc => ({
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

export default IssueDebitCard;
