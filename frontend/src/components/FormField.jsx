const FormField = ({ label, type = 'text', name, value, onChange, error, required = false, options = [], placeholder = '', helpText = '' }) => {
  return (
    <div className="mb-3">
      <label className="form-label" htmlFor={name}>
        {label}
      </label>
      {type === 'select' ? (
        <select
          id={name}
          name={name}
          value={value}
          onChange={onChange}
          className={`form-select ${error ? 'is-invalid' : ''}`}
          required={required}
        >
          {options.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      ) : type === 'textarea' ? (
        <textarea
          id={name}
          name={name}
          value={value}
          onChange={onChange}
          className={`form-control ${error ? 'is-invalid' : ''}`}
          required={required}
          rows={4}
          placeholder={placeholder}
        />
      ) : (
        <input
          type={type}
          id={name}
          name={name}
          value={value}
          onChange={onChange}
          className={`form-control ${error ? 'is-invalid' : ''}`}
          required={required}
          placeholder={placeholder}
          step={type === 'number' ? '0.01' : undefined}
        />
      )}
      {helpText && <small className="form-text text-muted">{helpText}</small>}
      {error && <div className="invalid-feedback d-block">{error}</div>}
    </div>
  );
};

export default FormField;
