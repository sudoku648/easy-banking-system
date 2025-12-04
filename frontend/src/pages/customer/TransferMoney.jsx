import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../../api/client';
import Layout from '../../components/Layout';
import { useTranslation } from 'react-i18next';
import { useLocale } from '../../contexts/LocaleContext';
import { getLocalizedUrl } from '../../config/routes';

const TransferMoney = () => {
  const { t } = useTranslation();
  const { currentLocale } = useLocale();
  const navigate = useNavigate();
  const [accounts, setAccounts] = useState([]);
  const [formData, setFormData] = useState({
    fromBankAccountId: '',
    toIban: '',
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
      const response = await api.get('/customer/accounts');
      setAccounts(response.data.accounts || []);
    } catch (err) {
      setError(t('Failed to load accounts'));
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
      // Note: The API expects sourceAccountId, recipientIban, amount (in cents), and title
      // But the Twig form doesn't have a title field, so we'll provide a default
      await api.post('/customer/transfer', {
        sourceAccountId: formData.fromBankAccountId,
        recipientIban: formData.toIban,
        amount: Math.round(parseFloat(formData.amount) * 100), // Convert to cents
        title: 'Transfer', // Default title since form doesn't have this field
      });
      setSuccess(t('Transfer initiated successfully!'));
      setFormData({
        fromBankAccountId: '',
        toIban: '',
        amount: '',
      });
      fetchAccounts(); // Refresh account balances
    } catch (err) {
      setError(err.response?.data?.message || t('Transfer failed'));
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    navigate(getLocalizedUrl('customer.dashboard', currentLocale));
  };

  return (
    <Layout>
      <div className="row">
        <div className="col-md-8 offset-md-2">
          <div className="card shadow-sm">
            <div className="card-header bg-primary text-white">
              <h2 className="mb-0">
                <i className="bi bi-arrow-left-right"></i> {t('transaction.make_transfer')}
              </h2>
            </div>
            <div className="card-body">
              <p className="text-muted">{t('dashboard.make_transfer_desc')}</p>

              {error && <div className="alert alert-danger">{error}</div>}
              {success && <div className="alert alert-success">{success}</div>}
              
              <form onSubmit={handleSubmit} noValidate>
                <div className="mb-3">
                  <div className="form-group">
                    <label htmlFor="fromBankAccountId" className="form-label">
                      {t('transaction.select_bank_account')}
                    </label>
                    <select
                      id="fromBankAccountId"
                      name="fromBankAccountId"
                      className="form-select"
                      value={formData.fromBankAccountId}
                      onChange={handleChange}
                      required
                    >
                      <option value="">{t('transaction.placeholder_select_source_account')}</option>
                      {accounts.map((account) => (
                        <option key={account.id} value={account.id}>
                          {account.iban} ({(account.availableBalance / 100).toFixed(2)} {account.currency})
                        </option>
                      ))}
                    </select>
                    <small className="form-text text-muted">
                      {t('transaction.help_select_source_account')}
                    </small>
                  </div>
                </div>

                <div className="mb-3">
                  <div className="form-group">
                    <label htmlFor="toIban" className="form-label">
                      {t('transaction.iban')}
                    </label>
                    <input
                      type="text"
                      id="toIban"
                      name="toIban"
                      className="form-control"
                      value={formData.toIban}
                      onChange={handleChange}
                      placeholder={t('transaction.placeholder_to_iban')}
                      required
                    />
                    <small className="form-text text-muted">
                      {t('transaction.help_enter_destination_iban')}
                    </small>
                  </div>
                </div>

                <div className="mb-3">
                  <div className="form-group">
                    <label htmlFor="amount" className="form-label">
                      {t('transaction.amount')}
                    </label>
                    <div className="input-group">
                      <input
                        type="number"
                        id="amount"
                        name="amount"
                        className="form-control"
                        value={formData.amount}
                        onChange={handleChange}
                        placeholder={t('transaction.placeholder_amount')}
                        step="0.01"
                        min="0.01"
                        required
                      />
                    </div>
                    <small className="form-text text-muted">
                      {t('transaction.help_enter_amount')}
                    </small>
                  </div>
                </div>

                <div className="alert alert-info">
                  <i className="bi bi-info-circle"></i> {t('transaction.currency_exchange_note')}
                </div>

                <div className="d-flex justify-content-between">
                  <button type="button" className="btn btn-secondary" onClick={handleCancel}>
                    <i className="bi bi-arrow-left"></i> {t('common.cancel')}
                  </button>
                  <button type="submit" className="btn btn-primary" disabled={loading}>
                    <i className="bi bi-check-circle"></i> {loading ? t('Processing...') : t('dashboard.transfer_money')}
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

export default TransferMoney;
