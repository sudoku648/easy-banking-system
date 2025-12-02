/**
 * Transaction History React Component with Pagination
 * Manages pagination state and fetches transaction data from API
 */

class TransactionHistory extends React.Component {
    constructor(props) {
        super(props);

        // Read saved preference from cookie
        const savedLimit = this.getCookie('transaction_rows_per_page');

        this.state = {
            transactions: [],
            loading: true,
            error: null,
            page: 1,
            limit: savedLimit ? parseInt(savedLimit) : 10,
            total: 0,
            totalPages: 0,
            customerName: null,
            accountIban: null
        };
    }

    componentDidMount() {
        this.fetchTransactions();
    }

    componentDidUpdate(prevProps, prevState) {
        // Refetch if page or limit changes
        if (prevState.page !== this.state.page || prevState.limit !== this.state.limit) {
            this.fetchTransactions();
        }
    }

    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    setCookie(name, value, days = 365) {
        const d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = `expires=${d.toUTCString()}`;
        document.cookie = `${name}=${value};${expires};path=/;SameSite=Lax`;
    }

    async fetchTransactions() {
        this.setState({ loading: true, error: null });

        const { page, limit } = this.state;
        const { apiUrl, customerId, bankAccountId } = this.props;

        try {
            let url = `${apiUrl}?page=${page}&limit=${limit}`;

            if (customerId) {
                url += `&customerId=${customerId}`;
            }
            if (bankAccountId) {
                url += `&bankAccountId=${bankAccountId}`;
            }

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            this.setState({
                transactions: data.transactions,
                total: data.total,
                totalPages: data.totalPages,
                customerName: data.customerName || null,
                accountIban: data.accountIban || null,
                loading: false
            });
        } catch (error) {
            console.error('Error fetching transactions:', error);
            this.setState({
                error: error.message,
                loading: false
            });
        }
    }

    handlePageChange(newPage) {
        if (newPage >= 1 && newPage <= this.state.totalPages) {
            this.setState({ page: newPage });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    handleLimitChange(newLimit) {
        this.setState({ 
            limit: parseInt(newLimit), 
            page: 1 
        });
        this.setCookie('transaction_rows_per_page', newLimit);
    }

    formatAmount(amount) {
        return parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    getTransactionTypeLabel(type) {
        const { translations } = this.props;
        const typeMap = {
            'TRANSFER_DEPOSIT': translations.transfer_deposit,
            'TRANSFER_WITHDRAWAL': translations.transfer_withdrawal,
            'CASH_WITHDRAWAL': translations.cash_withdrawal,
            'CASH_DEPOSIT': translations.cash_deposit,
            'ATM_WITHDRAWAL': translations.atm_withdrawal
        };
        return typeMap[type] || type;
    }

    getTransactionTypeClass(type) {
        const classMap = {
            'TRANSFER_DEPOSIT': 'success',
            'TRANSFER_WITHDRAWAL': 'warning',
            'CASH_WITHDRAWAL': 'danger',
            'CASH_DEPOSIT': 'primary',
            'ATM_WITHDRAWAL': 'warning'
        };
        return classMap[type] || 'secondary';
    }

    getTransactionTypeIcon(type) {
        const iconMap = {
            'TRANSFER_DEPOSIT': 'bi-arrow-down-circle',
            'TRANSFER_WITHDRAWAL': 'bi-arrow-up-circle',
            'CASH_WITHDRAWAL': 'bi-cash',
            'CASH_DEPOSIT': 'bi-cash-coin',
            'ATM_WITHDRAWAL': 'bi-credit-card'
        };
        return iconMap[type] || 'bi-circle';
    }

    getStatusLabel(status) {
        const { translations } = this.props;
        const statusMap = {
            'ORDERED': translations.status_ordered,
            'EXECUTED': translations.status_executed,
            'CANCELED': translations.status_canceled
        };
        return statusMap[status] || status;
    }

    getStatusClass(status) {
        const classMap = {
            'ORDERED': 'warning',
            'EXECUTED': 'success',
            'CANCELED': 'danger'
        };
        return classMap[status] || 'secondary';
    }

    getStatusIcon(status) {
        const iconMap = {
            'ORDERED': 'bi-clock',
            'EXECUTED': 'bi-check-circle',
            'CANCELED': 'bi-x-circle'
        };
        return iconMap[status] || 'bi-circle';
    }

    renderPagination() {
        const { page, totalPages, total, limit } = this.state;
        const { translations } = this.props;

        if (totalPages <= 1) {
            return null;
        }

        const pages = [];
        const maxPagesToShow = 5;
        let startPage = Math.max(1, page - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

        if (endPage - startPage < maxPagesToShow - 1) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
        }

        const from = (page - 1) * limit + 1;
        const to = Math.min(page * limit, total);

        const paginationItems = [];

        // Previous button
        paginationItems.push(
            React.createElement('li', {
                key: 'prev',
                className: `page-item ${page === 1 ? 'disabled' : ''}`
            },
                React.createElement('button', {
                    className: 'page-link',
                    onClick: () => this.handlePageChange(page - 1),
                    disabled: page === 1
                }, translations.previous)
            )
        );

        // First page + ellipsis
        if (startPage > 1) {
            paginationItems.push(
                React.createElement('li', {
                    key: 1,
                    className: 'page-item'
                },
                    React.createElement('button', {
                        className: 'page-link',
                        onClick: () => this.handlePageChange(1)
                    }, '1')
                )
            );
            
            if (startPage > 2) {
                paginationItems.push(
                    React.createElement('li', {
                        key: 'ellipsis-start',
                        className: 'page-item disabled'
                    },
                        React.createElement('span', { className: 'page-link' }, '...')
                    )
                );
            }
        }

        // Page numbers
        pages.forEach(p => {
            paginationItems.push(
                React.createElement('li', {
                    key: p,
                    className: `page-item ${p === page ? 'active' : ''}`
                },
                    React.createElement('button', {
                        className: 'page-link',
                        onClick: () => this.handlePageChange(p)
                    }, p)
                )
            );
        });

        // Last page + ellipsis
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationItems.push(
                    React.createElement('li', {
                        key: 'ellipsis-end',
                        className: 'page-item disabled'
                    },
                        React.createElement('span', { className: 'page-link' }, '...')
                    )
                );
            }
            
            paginationItems.push(
                React.createElement('li', {
                    key: totalPages,
                    className: 'page-item'
                },
                    React.createElement('button', {
                        className: 'page-link',
                        onClick: () => this.handlePageChange(totalPages)
                    }, totalPages)
                )
            );
        }

        // Next button
        paginationItems.push(
            React.createElement('li', {
                key: 'next',
                className: `page-item ${page === totalPages ? 'disabled' : ''}`
            },
                React.createElement('button', {
                    className: 'page-link',
                    onClick: () => this.handlePageChange(page + 1),
                    disabled: page === totalPages
                }, translations.next)
            )
        );

        return React.createElement('div', {
            className: 'd-flex justify-content-between align-items-center mt-4'
        },
            React.createElement('div', { className: 'text-muted' },
                translations.showing_records
                    .replace('{from}', from)
                    .replace('{to}', to)
                    .replace('{total}', total)
            ),
            React.createElement('nav', null,
                React.createElement('ul', { className: 'pagination mb-0' },
                    ...paginationItems
                )
            )
        );
    }

    renderRowsPerPageSelector() {
        const { limit } = this.state;
        const { translations } = this.props;

        return React.createElement('div', {
            className: 'd-flex align-items-center mb-3'
        },
            React.createElement('label', { className: 'me-2 mb-0' },
                translations.rows_per_page + ':'
            ),
            React.createElement('select', {
                className: 'form-select form-select-sm',
                style: { width: 'auto' },
                value: limit,
                onChange: (e) => this.handleLimitChange(e.target.value)
            },
                React.createElement('option', { value: '10' }, '10'),
                React.createElement('option', { value: '20' }, '20'),
                React.createElement('option', { value: '50' }, '50')
            )
        );
    }

    renderCancelButton(transaction) {
        const { translations, cancelUrl, showCancelButton, customerId, bankAccountId } = this.props;

        if (!showCancelButton || transaction.status !== 'ORDERED') {
            return React.createElement('span', { className: 'text-muted' }, '-');
        }

        const formChildren = [
            React.createElement('input', {
                key: 'transactionId',
                type: 'hidden',
                name: 'transactionId',
                value: transaction.id
            })
        ];

        if (customerId) {
            formChildren.push(
                React.createElement('input', {
                    key: 'customerId',
                    type: 'hidden',
                    name: 'customerId',
                    value: customerId
                })
            );
        }

        if (bankAccountId) {
            formChildren.push(
                React.createElement('input', {
                    key: 'bankAccountId',
                    type: 'hidden',
                    name: 'bankAccountId',
                    value: bankAccountId
                })
            );
        }

        formChildren.push(
            React.createElement('button', {
                key: 'submit',
                type: 'submit',
                className: 'btn btn-sm btn-danger'
            },
                React.createElement('i', { className: 'bi bi-x-circle' }),
                ' ' + translations.cancel_transfer
            )
        );

        return React.createElement('form', {
            method: 'post',
            action: cancelUrl,
            style: { display: 'inline' },
            onSubmit: (e) => {
                if (!confirm(translations.confirm_cancel)) {
                    e.preventDefault();
                }
            }
        }, ...formChildren);
    }

    render() {
        const { loading, error, transactions, total } = this.state;
        const { translations, showCancelButton } = this.props;

        if (loading) {
            return React.createElement('div', {
                className: 'text-center py-5'
            },
                React.createElement('div', {
                    className: 'spinner-border text-primary',
                    role: 'status'
                },
                    React.createElement('span', { className: 'visually-hidden' },
                        translations.loading
                    )
                ),
                React.createElement('p', { className: 'mt-2' }, translations.loading)
            );
        }

        if (error) {
            return React.createElement('div', { className: 'alert alert-danger' },
                React.createElement('i', { className: 'bi bi-exclamation-triangle' }),
                ' Error: ' + error
            );
        }

        if (total === 0) {
            return React.createElement('div', { className: 'alert alert-info' },
                React.createElement('i', { className: 'bi bi-info-circle' }),
                ' ' + translations.no_data
            );
        }

        const headerCells = [
            React.createElement('th', { key: 'date' }, translations.date),
            React.createElement('th', { key: 'iban' }, translations.iban),
            React.createElement('th', { key: 'type' }, translations.type),
            React.createElement('th', { key: 'status' }, translations.status),
            React.createElement('th', { key: 'amount', className: 'text-end' }, translations.amount),
            React.createElement('th', { key: 'original_amount', className: 'text-end' }, translations.original_amount),
            React.createElement('th', { key: 'exchange_rate', className: 'text-center' }, translations.exchange_rate)
        ];

        if (showCancelButton) {
            headerCells.push(
                React.createElement('th', { key: 'actions', className: 'text-center' }, translations.actions)
            );
        }

        const tableRows = transactions.map(transaction => {
            const cells = [
                React.createElement('td', { key: 'date' },
                    React.createElement('small', null, transaction.occurredAt)
                ),
                React.createElement('td', { key: 'iban' },
                    React.createElement('code', null, transaction.accountIban)
                ),
                React.createElement('td', { key: 'type' },
                    React.createElement('span', {
                        className: `badge bg-${this.getTransactionTypeClass(transaction.type)}`
                    },
                        React.createElement('i', {
                            className: `bi ${this.getTransactionTypeIcon(transaction.type)}`
                        }),
                        ' ' + this.getTransactionTypeLabel(transaction.type)
                    )
                ),
                React.createElement('td', { key: 'status' },
                    React.createElement('span', {
                        className: `badge bg-${this.getStatusClass(transaction.status)}`
                    },
                        React.createElement('i', {
                            className: `bi ${this.getStatusIcon(transaction.status)}`
                        }),
                        ' ' + this.getStatusLabel(transaction.status)
                    )
                ),
                React.createElement('td', { key: 'amount', className: 'text-end' },
                    (transaction.type === 'TRANSFER_DEPOSIT' || transaction.type === 'CASH_DEPOSIT')
                        ? React.createElement('span', { className: 'text-success' },
                            '+' + this.formatAmount(transaction.amount)
                        )
                        : React.createElement('span', { className: 'text-danger' },
                            '-' + this.formatAmount(transaction.amount)
                        ),
                    ' ',
                    React.createElement('span', { className: 'badge bg-secondary' }, transaction.currency)
                ),
                React.createElement('td', { key: 'original_amount', className: 'text-end' },
                    this.formatAmount(transaction.originalAmount),
                    ' ',
                    React.createElement('span', { className: 'badge bg-secondary' }, transaction.originalCurrency)
                ),
                React.createElement('td', { key: 'exchange_rate', className: 'text-center' },
                    transaction.exchangeRate !== 1.0
                        ? React.createElement('span', { className: 'badge bg-info' },
                            transaction.exchangeRate.toFixed(4)
                        )
                        : React.createElement('span', { className: 'text-muted' }, '-')
                )
            ];

            if (showCancelButton) {
                cells.push(
                    React.createElement('td', { key: 'actions', className: 'text-center' },
                        this.renderCancelButton(transaction)
                    )
                );
            }

            return React.createElement('tr', { key: transaction.id }, ...cells);
        });

        return React.createElement('div', null,
            this.renderRowsPerPageSelector(),
            React.createElement('div', { className: 'table-responsive' },
                React.createElement('table', { className: 'table table-hover' },
                    React.createElement('thead', null,
                        React.createElement('tr', null, ...headerCells)
                    ),
                    React.createElement('tbody', null, ...tableRows)
                )
            ),
            this.renderPagination()
        );
    }
}
