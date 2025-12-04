import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../../api/client';
import Layout from '../../components/Layout';
import FormCard from '../../components/FormCard';
import FormField from '../../components/FormField';

const OpenAccountExistingCustomer = () => {
  const { t } = useTranslation();
  const [customers, setCustomers] = useState([]);
  const [formData, setFormData] = useState({
    customerId: '',
    currency: 'PLN',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    fetchCustomers();
  }, []);

  const fetchCustomers = async () => {
    try {
      const response = await api.get('/employee/customers');
      setCustomers(response.data.data.customers || []);
    } catch (err) {
      console.error('Failed to load customers', err);
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
      const response = await api.post('/employee/open-account-existing-customer', formData);
      setSuccess(response.data.message || t('bank_account.account_created_successfully'));
      setFormData({
        customerId: '',
        currency: 'PLN',
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
        title={t('bank_account.open_for_existing_customer')}
        icon="person-check"
        color="success"
        description={t('bank_account.open_for_existing_customer')}
        cancelRoute="employee.dashboard"
        submitText={t('bank_account.open_new')}
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
            label={t('bank_account.select_customer')}
            type="select"
            name="customerId"
            value={formData.customerId}
            onChange={handleChange}
            helpText={t('bank_account.help_select_customer')}
            options={[
              { value: '', label: t('bank_account.placeholder_select_customer') },
              ...customers.map(c => ({ value: c.id, label: `${c.firstName} ${c.lastName}` }))
            ]}
            required
          />
        </div>

        <div className="mb-3">
          <FormField
            label={t('bank_account.currency')}
            type="select"
            name="currency"
            value={formData.currency}
            onChange={handleChange}
            helpText={t('bank_account.help_select_currency')}
            options={[
              { value: 'PLN', label: 'PLN' },
              { value: 'EUR', label: 'EUR' },
              { value: 'USD', label: 'USD' },
              { value: 'GBP', label: 'GBP' },
            ]}
            required
          />
        </div>
      </FormCard>
    </Layout>
  );
};

export default OpenAccountExistingCustomer;
