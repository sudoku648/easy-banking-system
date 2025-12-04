import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../../api/client';
import Layout from '../../components/Layout';
import FormCard from '../../components/FormCard';

const ChangePassword = () => {
  const { t } = useTranslation();
  const [formData, setFormData] = useState({
    currentPassword: '',
    newPassword: '',
    confirmNewPassword: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

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

    if (formData.newPassword !== formData.confirmNewPassword) {
      setError(t('user.passwords_do_not_match'));
      return;
    }

    setLoading(true);

    try {
      await api.post('/customer/change-password', {
        currentPassword: formData.currentPassword,
        newPassword: formData.newPassword,
      });
      setSuccess(t('user.password_changed_successfully'));
      setFormData({
        currentPassword: '',
        newPassword: '',
        confirmNewPassword: '',
      });
    } catch (err) {
      setError(err.response?.data?.message || t('user.failed_to_change_password'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <Layout>
      <FormCard
        title={t('user.change_password')}
        icon="key"
        color="warning"
        description={t('user.change_password_description')}
        cancelRoute="customer.dashboard"
        submitText={t('user.change_password_submit')}
        submitIcon="check-circle"
        onSubmit={handleSubmit}
        loading={loading}
        alert={
          <>
            {error && <div className="alert alert-danger">{error}</div>}
            {success && <div className="alert alert-success">{success}</div>}
          </>
        }
      >
        <div className="mb-3">
          <div className="form-group">
            <label htmlFor="currentPassword" className="form-label">
              {t('user.current_password')}
            </label>
            <input
              type="password"
              className="form-control"
              id="currentPassword"
              name="currentPassword"
              value={formData.currentPassword}
              onChange={handleChange}
              placeholder={t('user.placeholder_current_password')}
              autoComplete="current-password"
              required
            />
            <small className="form-text text-muted">
              {t('user.help_current_password')}
            </small>
          </div>
        </div>

        <div className="mb-3">
          <div className="form-group">
            <label htmlFor="newPassword" className="form-label">
              {t('user.new_password')}
            </label>
            <input
              type="password"
              className="form-control"
              id="newPassword"
              name="newPassword"
              value={formData.newPassword}
              onChange={handleChange}
              placeholder={t('user.placeholder_new_password')}
              autoComplete="new-password"
              required
            />
            <small className="form-text text-muted">
              {t('user.help_new_password')}
            </small>
          </div>
        </div>

        <div className="mb-3">
          <div className="form-group">
            <label htmlFor="confirmNewPassword" className="form-label">
              {t('user.confirm_new_password')}
            </label>
            <input
              type="password"
              className="form-control"
              id="confirmNewPassword"
              name="confirmNewPassword"
              value={formData.confirmNewPassword}
              onChange={handleChange}
              placeholder={t('user.placeholder_confirm_new_password')}
              autoComplete="new-password"
              required
            />
            <small className="form-text text-muted">
              {t('user.help_confirm_new_password')}
            </small>
          </div>
        </div>

        <div className="alert alert-info">
          <i className="bi bi-info-circle"></i> {t('user.password_security_note')}
        </div>
      </FormCard>
    </Layout>
  );
};

export default ChangePassword;
