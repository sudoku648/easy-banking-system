import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../../api/client';
import Layout from '../../components/Layout';
import FormCard from '../../components/FormCard';
import FormField from '../../components/FormField';

const BlockDebitCard = () => {
  const { t } = useTranslation();
  const [debitCards, setDebitCards] = useState([]);
  const [formData, setFormData] = useState({
    cardId: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    fetchDebitCards();
  }, []);

  const fetchDebitCards = async () => {
    try {
      const response = await api.get('/employee/debit-cards');
      const cards = response.data.data || [];
      setDebitCards(cards);
    } catch (err) {
      setError(t('common.error_occurred'));
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
      const response = await api.post('/employee/block-debit-card', formData);
      setSuccess(response.data.message || t('bank_account.card_blocked_successfully'));
      setFormData({
        cardId: '',
      });
      fetchDebitCards();
    } catch (err) {
      setError(err.response?.data?.message || t('common.error_occurred'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <Layout>
      <FormCard
        title={t('bank_account.block_debit_card')}
        icon="slash-circle"
        color="danger"
        description={t('bank_account.block_debit_card_description')}
        cancelRoute="employee.dashboard"
        submitText={t('bank_account.block_card')}
        submitIcon="x-circle"
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
          <div className="form-group">
            <FormField
              label={t('bank_account.debit_card')}
              type="select"
              name="cardId"
              value={formData.cardId}
              onChange={handleChange}
              helpText={t('bank_account.help_select_card_to_block')}
              required
              options={[
                { value: '', label: t('bank_account.placeholder_select_debit_card_to_block') },
                ...debitCards.map((card) => ({
                  value: card.id,
                  label: card.label,
                }))
              ]}
            />
          </div>
        </div>
      </FormCard>
    </Layout>
  );
};

export default BlockDebitCard;
