import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import api from '../../api/client';
import { useLocaleNavigate } from '../../hooks/useLocaleNavigate';
import Layout from '../../components/Layout';
import Card from '../../components/Card';

const TransactionHistory = () => {
  const { customerId, bankAccountId } = useParams();
  const { t } = useTranslation();
  const navigate = useLocaleNavigate();
  
  const [transactions, setTransactions] = useState([]);
  const [customerName, setCustomerName] = useState('');
  const [accountIban, setAccountIban] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(10);
  const [totalPages, setTotalPages] = useState(0);
  const [total, setTotal] = useState(0);

  useEffect(() => {
    fetchTransactions();
  }, [customerId, bankAccountId, page, limit]);

  const fetchTransactions = async () => {
    setLoading(true);
    try {
      const params = {
        page,
        limit,
        ...(bankAccountId && { bankAccountId }),
        ...(customerId && { customerId }),
      };

      const response = await api.get('/employee/transaction/history/api', { params });
      
      setTransactions(response.data.transactions || []);
      setTotal(response.data.total || 0);
      setTotalPages(response.data.totalPages || 0);
      setCustomerName(response.data.customerName || '');
      setAccountIban(response.data.accountIban || '');
    } catch (err) {
      setError(err.response?.data?.error || t('common.error_loading_data'));
    } finally {
      setLoading(false);
    }
  };

  const handleCancelTransaction = async (transactionId) => {
    if (!window.confirm(t('transaction.confirm_cancel'))) {
      return;
    }

    try {
      const formData = new FormData();
      formData.append('transactionId', transactionId);
      if (customerId) formData.append('customerId', customerId);
      if (bankAccountId) formData.append('bankAccountId', bankAccountId);

      await fetch('/employee/transaction/cancel', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      
      fetchTransactions();
    } catch (err) {
      setError(t('common.error_occurred'));
    }
  };

  const getTransactionTypeBadge = (type) => {
    const typeMap = {
      'TRANSFER_DEPOSIT': { color: 'success', icon: 'arrow-down-circle', label: t('transaction.transfer_deposit') },
      'TRANSFER_WITHDRAWAL': { color: 'warning', icon: 'arrow-up-circle', label: t('transaction.transfer_withdrawal') },
      'CASH_WITHDRAWAL': { color: 'danger', icon: 'cash', label: t('transaction.cash_withdrawal') },
      'ATM_WITHDRAWAL': { color: 'warning', icon: 'credit-card', label: t('transaction.atm_withdrawal') },
      'CASH_DEPOSIT': { color: 'primary', icon: 'cash-coin', label: t('transaction.cash_deposit') },
    };
    
    const config = typeMap[type] || { color: 'secondary', icon: 'question', label: type };
    
    return (
      <span className={`badge bg-${config.color}`}>
        <i className={`bi bi-${config.icon}`}></i> {config.label}
      </span>
    );
  };

  const getStatusBadge = (status) => {
    const statusMap = {
      'ORDERED': { color: 'warning', icon: 'clock', label: t('transaction.status_ordered') },
      'EXECUTED': { color: 'success', icon: 'check-circle', label: t('transaction.status_executed') },
      'CANCELED': { color: 'danger', icon: 'x-circle', label: t('transaction.status_canceled') },
    };
    
    const config = statusMap[status] || { color: 'secondary', icon: 'question', label: status };
    
    return (
      <span className={`badge bg-${config.color}`}>
        <i className={`bi bi-${config.icon}`}></i> {config.label}
      </span>
    );
  };

  if (loading && transactions.length === 0) {
    return (
      <Layout>
        <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">{t('transaction.loading')}</span>
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
                <i className="bi bi-clock-history"></i> {t('transaction.customer_history_title')}
              </h2>
              {accountIban && (
                <p className="mb-0 mt-2">
                  <small>{t('bank_account.iban')}: <code>{accountIban}</code></small>
                </p>
              )}
              {!accountIban && customerName && (
                <p className="mb-0 mt-2">
                  <small>{customerName}</small>
                </p>
              )}
            </div>
            <div className="card-body">
              {error && <div className="alert alert-danger">{error}</div>}

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
                          <th className="text-center">{t('transaction.actions')}</th>
                        </tr>
                      </thead>
                      <tbody>
                        {transactions.map((transaction) => (
                          <tr key={transaction.id}>
                            <td>
                              <small>{transaction.occurredAt}</small>
                            </td>
                            <td>
                              <code>{transaction.accountIban}</code>
                            </td>
                            <td>{getTransactionTypeBadge(transaction.type)}</td>
                            <td>{getStatusBadge(transaction.status)}</td>
                            <td className="text-end">
                              <span className={transaction.type.includes('DEPOSIT') ? 'text-success' : 'text-danger'}>
                                {transaction.type.includes('DEPOSIT') ? '+' : '-'}
                                {transaction.amount.toFixed(2)}
                              </span>
                              {' '}
                              <span className="badge bg-secondary">{transaction.currency}</span>
                            </td>
                            <td className="text-end">
                              {transaction.originalAmount.toFixed(2)}
                              {' '}
                              <span className="badge bg-secondary">{transaction.originalCurrency}</span>
                            </td>
                            <td className="text-center">
                              {transaction.exchangeRate !== 1.0 ? (
                                <span className="badge bg-info">{transaction.exchangeRate.toFixed(4)}</span>
                              ) : (
                                <span className="text-muted">-</span>
                              )}
                            </td>
                            <td className="text-center">
                              {transaction.status === 'ORDERED' ? (
                                <button
                                  type="button"
                                  className="btn btn-sm btn-danger"
                                  onClick={() => handleCancelTransaction(transaction.id)}
                                >
                                  <i className="bi bi-x-circle"></i> {t('transaction.cancel_transfer')}
                                </button>
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
                        
                        {/* First page + ellipsis */}
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
                <button
                  type="button"
                  className="btn btn-secondary me-2"
                  onClick={() => navigate('employee.selectCustomer')}
                >
                  <i className="bi bi-arrow-left"></i> {t('common.back')}
                </button>
                <button
                  type="button"
                  className="btn btn-secondary"
                  onClick={() => navigate('employee.dashboard')}
                >
                  <i className="bi bi-speedometer2"></i> {t('dashboard.go_to_dashboard')}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default TransactionHistory;
