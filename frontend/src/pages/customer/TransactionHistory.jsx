import { useState, useEffect } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import api from '../../api/client';
import { useLocale } from '../../contexts/LocaleContext';
import { getLocalizedUrl } from '../../config/routes';
import Layout from '../../components/Layout';

const TransactionHistory = () => {
  const { t } = useTranslation();
  const { locale: urlLocale } = useParams();
  const { locale } = useLocale();
  const currentLocale = urlLocale || locale;
  const [transactions, setTransactions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(10);
  const [totalPages, setTotalPages] = useState(0);
  const [total, setTotal] = useState(0);

  useEffect(() => {
    const fetchTransactions = async () => {
      setLoading(true);
      try {
        const response = await api.get('/customer/transaction/history', {
          params: { page, limit }
        });
        setTransactions(response.data.transactions || []);
        setTotal(response.data.total || 0);
        setTotalPages(response.data.totalPages || 0);
      } catch (err) {
        setError(t('transaction.error_loading'));
      } finally {
        setLoading(false);
      }
    };

    fetchTransactions();
  }, [page, limit, t]);

  const getTransactionTypeBadge = (type) => {
    const types = {
      TRANSFER_DEPOSIT: { class: 'success', icon: 'arrow-down-circle', label: t('transaction.transfer_deposit') },
      TRANSFER_WITHDRAWAL: { class: 'warning', icon: 'arrow-up-circle', label: t('transaction.transfer_withdrawal') },
      CASH_WITHDRAWAL: { class: 'danger', icon: 'cash', label: t('transaction.cash_withdrawal') },
      ATM_WITHDRAWAL: { class: 'warning', icon: 'credit-card', label: t('transaction.atm_withdrawal') },
      CASH_DEPOSIT: { class: 'primary', icon: 'cash-coin', label: t('transaction.cash_deposit') }
    };
    const typeInfo = types[type] || { class: 'secondary', icon: 'circle', label: type };
    return (
      <span className={`badge bg-${typeInfo.class}`}>
        <i className={`bi bi-${typeInfo.icon}`}></i> {typeInfo.label}
      </span>
    );
  };

  const getStatusBadge = (status) => {
    const statuses = {
      ORDERED: { class: 'warning', icon: 'clock', label: t('transaction.status_ordered') },
      EXECUTED: { class: 'success', icon: 'check-circle', label: t('transaction.status_executed') },
      CANCELED: { class: 'danger', icon: 'x-circle', label: t('transaction.status_canceled') }
    };
    const statusInfo = statuses[status] || { class: 'secondary', icon: 'circle', label: status };
    return (
      <span className={`badge bg-${statusInfo.class}`}>
        <i className={`bi bi-${statusInfo.icon}`}></i> {statusInfo.label}
      </span>
    );
  };

  const formatAmount = (amount, type) => {
    const formatted = Math.abs(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const isDeposit = type === 'TRANSFER_DEPOSIT' || type === 'CASH_DEPOSIT';
    return isDeposit ? (
      <span className="text-success">+{formatted}</span>
    ) : (
      <span className="text-danger">-{formatted}</span>
    );
  };

  if (loading && transactions.length === 0) {
    return (
      <Layout>
        <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">{t('common.loading')}</span>
          </div>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="row">
        <div className="col-12">
          <div className="card shadow-sm">
            <div className="card-header bg-info text-white">
              <h2 className="mb-0">
                <i className="bi bi-clock-history"></i> {t('transaction.history_title')}
              </h2>
            </div>
            <div className="card-body">
              {error && (
                <div className="alert alert-danger alert-dismissible fade show">
                  <i className="bi bi-exclamation-triangle"></i> {error}
                  <button type="button" className="btn-close" onClick={() => setError('')}></button>
                </div>
              )}

              {transactions.length === 0 ? (
                <div className="alert alert-info">
                  <i className="bi bi-info-circle"></i> {t('transaction.no_transactions')}
                </div>
              ) : (
                <>
                  {/* Rows per page selector */}
                  <div className="d-flex align-items-center mb-3">
                    <label htmlFor="limit" className="me-2 mb-0">
                      {t('transaction.rows_per_page')}:
                    </label>
                    <select
                      id="limit"
                      className="form-select form-select-sm"
                      style={{ width: 'auto' }}
                      value={limit}
                      onChange={(e) => {
                        setLimit(Number(e.target.value));
                        setPage(1);
                      }}
                    >
                      <option value="10">10</option>
                      <option value="20">20</option>
                      <option value="50">50</option>
                    </select>
                  </div>

                  {/* Transaction table */}
                  <div className="table-responsive">
                    <table className="table table-hover">
                      <thead>
                        <tr>
                          <th>{t('transaction.date')}</th>
                          <th>{t('bank_account.iban')}</th>
                          <th>{t('transaction.type')}</th>
                          <th>{t('transaction.status')}</th>
                          <th className="text-end">{t('transaction.amount')}</th>
                          <th className="text-end">{t('transaction.original_amount')}</th>
                          <th className="text-center">{t('transaction.exchange_rate')}</th>
                        </tr>
                      </thead>
                      <tbody>
                        {transactions.map((transaction) => (
                          <tr key={transaction.id}>
                            <td>
                              <small>{new Date(transaction.occurredAt).toLocaleString()}</small>
                            </td>
                            <td>
                              <code>{transaction.accountIban}</code>
                            </td>
                            <td>
                              {getTransactionTypeBadge(transaction.type)}
                            </td>
                            <td>
                              {getStatusBadge(transaction.status)}
                            </td>
                            <td className="text-end">
                              {formatAmount(transaction.amount, transaction.type)}{' '}
                              <span className="badge bg-secondary">{transaction.currency}</span>
                            </td>
                            <td className="text-end">
                              {Math.abs(transaction.originalAmount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}{' '}
                              <span className="badge bg-secondary">{transaction.originalCurrency}</span>
                            </td>
                            <td className="text-center">
                              {transaction.exchangeRate !== 1.0 ? (
                                <span className="badge bg-info">{transaction.exchangeRate.toFixed(4)}</span>
                              ) : (
                                <span className="text-muted">-</span>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  {/* Pagination footer */}
                  <div className="d-flex justify-content-between align-items-center mt-4">
                    <div className="text-muted">
                      {t('transaction.showing_records', {
                        from: (page - 1) * limit + 1,
                        to: Math.min(page * limit, total),
                        total
                      })}
                    </div>
                    <nav>
                      <ul className="pagination mb-0">
                        <li className={`page-item ${page === 1 ? 'disabled' : ''}`}>
                          <button
                            className="page-link"
                            onClick={() => setPage(page - 1)}
                            disabled={page === 1}
                          >
                            {t('transaction.previous')}
                          </button>
                        </li>
                        
                        {/* Page numbers with ellipsis */}
                        {(() => {
                          const maxPagesToShow = 5;
                          let startPage = Math.max(1, page - Math.floor(maxPagesToShow / 2));
                          let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
                          
                          if (endPage - startPage < maxPagesToShow - 1) {
                            startPage = Math.max(1, endPage - maxPagesToShow + 1);
                          }
                          
                          const pageButtons = [];
                          
                          // First page
                          if (startPage > 1) {
                            pageButtons.push(
                              <li key={1} className="page-item">
                                <button className="page-link" onClick={() => setPage(1)}>
                                  1
                                </button>
                              </li>
                            );
                            
                            if (startPage > 2) {
                              pageButtons.push(
                                <li key="ellipsis-start" className="page-item disabled">
                                  <span className="page-link">...</span>
                                </li>
                              );
                            }
                          }
                          
                          // Page numbers
                          for (let i = startPage; i <= endPage; i++) {
                            pageButtons.push(
                              <li key={i} className={`page-item ${i === page ? 'active' : ''}`}>
                                <button className="page-link" onClick={() => setPage(i)}>
                                  {i}
                                </button>
                              </li>
                            );
                          }
                          
                          // Last page
                          if (endPage < totalPages) {
                            if (endPage < totalPages - 1) {
                              pageButtons.push(
                                <li key="ellipsis-end" className="page-item disabled">
                                  <span className="page-link">...</span>
                                </li>
                              );
                            }
                            
                            pageButtons.push(
                              <li key={totalPages} className="page-item">
                                <button className="page-link" onClick={() => setPage(totalPages)}>
                                  {totalPages}
                                </button>
                              </li>
                            );
                          }
                          
                          return pageButtons;
                        })()}
                        
                        <li className={`page-item ${page === totalPages ? 'disabled' : ''}`}>
                          <button
                            className="page-link"
                            onClick={() => setPage(page + 1)}
                            disabled={page === totalPages}
                          >
                            {t('transaction.next')}
                          </button>
                        </li>
                      </ul>
                    </nav>
                  </div>
                </>
              )}

              <div className="mt-3">
                <Link to={getLocalizedUrl('customer.dashboard', currentLocale)} className="btn btn-secondary">
                  <i className="bi bi-arrow-left"></i> {t('common.back')}
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default TransactionHistory;
