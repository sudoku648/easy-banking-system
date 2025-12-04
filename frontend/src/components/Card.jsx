const Card = ({ title, children, className = '', headerBg = 'primary', icon = null, iconColor = null }) => {
  return (
    <div className={`card shadow-sm ${className}`}>
      {title && (
        <div className={`card-header bg-${headerBg} text-white`}>
          <h3 className="mb-0">
            {icon && <i className={`bi bi-${icon}${iconColor ? ` text-${iconColor}` : ''}`}></i>}
            {icon && ' '}
            {title}
          </h3>
        </div>
      )}
      <div className="card-body">
        {children}
      </div>
    </div>
  );
};

export default Card;
