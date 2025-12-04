import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../../api/client';
import Layout from '../../components/Layout';
import FormCard from '../../components/FormCard';
import FormField from '../../components/FormField';

const OpenAccountNewCustomer = () => {
  const { t } = useTranslation();
  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    username: '',
    password: '',
    permanentResidenceStreet: '',
    permanentResidenceCity: '',
    permanentResidencePostalCode1: '',
    permanentResidencePostalCode2: '',
    permanentResidenceCountry: 'Poland',
    sameAsPermament: false,
    correspondenceAddresses: [{
      street: '',
      city: '',
      postalCode1: '',
      postalCode2: '',
      country: 'Poland'
    }],
    currency: 'PLN',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData({
      ...formData,
      [name]: type === 'checkbox' ? checked : value,
    });
  };

  const handleCorrespondenceChange = (index, field, value) => {
    const newAddresses = [...formData.correspondenceAddresses];
    newAddresses[index] = { ...newAddresses[index], [field]: value };
    setFormData({ ...formData, correspondenceAddresses: newAddresses });
  };

  const addCorrespondenceAddress = () => {
    setFormData({
      ...formData,
      correspondenceAddresses: [
        ...formData.correspondenceAddresses,
        { street: '', city: '', postalCode1: '', postalCode2: '', country: 'Poland' }
      ]
    });
  };

  const removeCorrespondenceAddress = (index) => {
    const newAddresses = formData.correspondenceAddresses.filter((_, i) => i !== index);
    setFormData({ ...formData, correspondenceAddresses: newAddresses });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const response = await api.post('/employee/open-account-new-customer', formData);
      setSuccess(response.data.message || t('bank_account.account_created_successfully'));
      // Reset form
      setFormData({
        firstName: '',
        lastName: '',
        username: '',
        password: '',
        permanentResidenceStreet: '',
        permanentResidenceCity: '',
        permanentResidencePostalCode1: '',
        permanentResidencePostalCode2: '',
        permanentResidenceCountry: 'Poland',
        sameAsPermament: false,
        correspondenceAddresses: [{ street: '', city: '', postalCode1: '', postalCode2: '', country: 'Poland' }],
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
        title={t('bank_account.open_for_new_customer')}
        icon="person-plus"
        color="primary"
        description={t('bank_account.open_for_new_customer')}
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
        {/* Personal Information */}
        <h5 className="mb-3">{t('bank_account.personal_info')}</h5>
        <div className="row">
          <div className="col-md-6 mb-3">
            <FormField
              label={t('bank_account.first_name')}
              name="firstName"
              value={formData.firstName}
              onChange={handleChange}
              placeholder={t('bank_account.placeholder_first_name')}
              required
            />
          </div>
          <div className="col-md-6 mb-3">
            <FormField
              label={t('bank_account.last_name')}
              name="lastName"
              value={formData.lastName}
              onChange={handleChange}
              placeholder={t('bank_account.placeholder_last_name')}
              required
            />
          </div>
        </div>

        <div className="row">
          <div className="col-md-6 mb-3">
            <FormField
              label={t('bank_account.username')}
              name="username"
              value={formData.username}
              onChange={handleChange}
              placeholder={t('bank_account.placeholder_username')}
              helpText={t('bank_account.help_username')}
              required
            />
          </div>
          <div className="col-md-6 mb-3">
            <FormField
              label={t('bank_account.password')}
              type="password"
              name="password"
              value={formData.password}
              onChange={handleChange}
              placeholder={t('bank_account.placeholder_password')}
              helpText={t('bank_account.help_password')}
              required
            />
          </div>
        </div>

        {/* Permanent Residence Address */}
        <h5 className="mb-3 mt-4">{t('bank_account.permanent_residence')}</h5>
        <div className="card mb-3">
          <div className="card-body">
            <div className="mb-3">
              <FormField
                label={t('bank_account.permanent_residence_street')}
                name="permanentResidenceStreet"
                value={formData.permanentResidenceStreet}
                onChange={handleChange}
                placeholder={t('bank_account.placeholder_street')}
                required
              />
            </div>

            <div className="row">
              <div className="col-md-6 mb-3">
                <FormField
                  label={t('bank_account.permanent_residence_city')}
                  name="permanentResidenceCity"
                  value={formData.permanentResidenceCity}
                  onChange={handleChange}
                  placeholder={t('bank_account.placeholder_city')}
                  required
                />
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">{t('bank_account.permanent_residence_postal_code')}</label>
                <div className="d-flex align-items-center gap-2">
                  <input
                    type="text"
                    className="form-control"
                    style={{ width: '60px' }}
                    name="permanentResidencePostalCode1"
                    value={formData.permanentResidencePostalCode1}
                    onChange={handleChange}
                    maxLength="2"
                    required
                  />
                  <span>-</span>
                  <input
                    type="text"
                    className="form-control"
                    style={{ width: '80px' }}
                    name="permanentResidencePostalCode2"
                    value={formData.permanentResidencePostalCode2}
                    onChange={handleChange}
                    maxLength="3"
                    required
                  />
                </div>
              </div>
            </div>

            <div className="mb-3">
              <FormField
                label={t('bank_account.permanent_residence_country')}
                type="select"
                name="permanentResidenceCountry"
                value={formData.permanentResidenceCountry}
                onChange={handleChange}
                options={[
                  { value: '', label: t('bank_account.placeholder_select_country') },
                  { value: 'Poland', label: t('bank_account.countries.poland') },
                  { value: 'Germany', label: t('bank_account.countries.germany') },
                  { value: 'France', label: t('bank_account.countries.france') },
                  { value: 'UK', label: t('bank_account.countries.uk') },
                ]}
                required
              />
            </div>
          </div>
        </div>

        {/* Correspondence Address */}
        <h5 className="mb-3 mt-4">{t('bank_account.correspondence')}</h5>
        <div className="card mb-3">
          <div className="card-body">
            <div className="mb-3">
              <div className="form-check">
                <input
                  type="checkbox"
                  className="form-check-input"
                  id="sameAsPermament"
                  name="sameAsPermament"
                  checked={formData.sameAsPermament}
                  onChange={handleChange}
                />
                <label className="form-check-label" htmlFor="sameAsPermament">
                  {t('bank_account.same_as_permanent')}
                </label>
              </div>
            </div>

            {!formData.sameAsPermament && (
              <div>
                {formData.correspondenceAddresses.map((address, index) => (
                  <div key={index} className="border rounded p-3 mb-3 position-relative">
                    <div className="mb-3">
                      <label className="form-label">{t('bank_account.correspondence_street')}</label>
                      <input
                        type="text"
                        className="form-control"
                        value={address.street}
                        onChange={(e) => handleCorrespondenceChange(index, 'street', e.target.value)}
                        placeholder={t('bank_account.placeholder_street')}
                        required
                      />
                    </div>

                    <div className="row">
                      <div className="col-md-6 mb-3">
                        <label className="form-label">{t('bank_account.correspondence_city')}</label>
                        <input
                          type="text"
                          className="form-control"
                          value={address.city}
                          onChange={(e) => handleCorrespondenceChange(index, 'city', e.target.value)}
                          placeholder={t('bank_account.placeholder_city')}
                          required
                        />
                      </div>
                      <div className="col-md-6 mb-3">
                        <label className="form-label">{t('bank_account.correspondence_postal_code')}</label>
                        <div className="d-flex align-items-center gap-2">
                          <input
                            type="text"
                            className="form-control"
                            style={{ width: '60px' }}
                            value={address.postalCode1}
                            onChange={(e) => handleCorrespondenceChange(index, 'postalCode1', e.target.value)}
                            maxLength="2"
                            required
                          />
                          <span>-</span>
                          <input
                            type="text"
                            className="form-control"
                            style={{ width: '80px' }}
                            value={address.postalCode2}
                            onChange={(e) => handleCorrespondenceChange(index, 'postalCode2', e.target.value)}
                            maxLength="3"
                            required
                          />
                        </div>
                      </div>
                    </div>

                    <div className="mb-3">
                      <label className="form-label">{t('bank_account.correspondence_country')}</label>
                      <select
                        className="form-select"
                        value={address.country}
                        onChange={(e) => handleCorrespondenceChange(index, 'country', e.target.value)}
                        required
                      >
                        <option value="">{t('bank_account.placeholder_select_country')}</option>
                        <option value="Poland">{t('bank_account.countries.poland')}</option>
                        <option value="Germany">{t('bank_account.countries.germany')}</option>
                        <option value="France">{t('bank_account.countries.france')}</option>
                        <option value="UK">{t('bank_account.countries.uk')}</option>
                      </select>
                    </div>

                    {index > 0 && (
                      <button
                        type="button"
                        className="btn btn-sm btn-danger position-absolute top-0 end-0 m-2"
                        onClick={() => removeCorrespondenceAddress(index)}
                      >
                        <i className="bi bi-trash"></i> {t('bank_account.remove_address')}
                      </button>
                    )}
                  </div>
                ))}

                <button
                  type="button"
                  className="btn btn-secondary btn-sm"
                  onClick={addCorrespondenceAddress}
                >
                  <i className="bi bi-plus-circle"></i> {t('bank_account.add_correspondence_address')}
                </button>
              </div>
            )}
          </div>
        </div>

        {/* Account Settings */}
        <h5 className="mb-3 mt-4">{t('bank_account.account_settings')}</h5>
        <div className="mb-3">
          <FormField
            label={t('bank_account.currency')}
            type="select"
            name="currency"
            value={formData.currency}
            onChange={handleChange}
            helpText={t('bank_account.help_select_currency_new')}
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

export default OpenAccountNewCustomer;
