/**
 * Dynamic customer and account selector for transaction history
 * 
 * Features:
 * - When a customer is selected, the account dropdown is filtered to show only that customer's accounts
 * - When an account is selected, the customer dropdown is auto-filled with the account owner
 * - If a customer has only one account, it's automatically selected when the customer is chosen
 * - If no customer is selected, all accounts are shown
 * 
 * Requirements:
 * - Account options must have a data-customer-id attribute linking them to their owner
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const customerSelect = document.getElementById('select_customer_for_history_form_customerId');
        const accountSelect = document.getElementById('select_customer_for_history_form_bankAccountId');

        if (!customerSelect || !accountSelect) {
            return; // Not on the right page
        }

        // Store all account options with their customer IDs
        const allAccountOptions = [];
        const accountsByCustomer = {};

        // Parse accounts data from data attributes
        Array.from(accountSelect.options).forEach(option => {
            if (option.value) {
                const customerId = option.dataset.customerId;
                const optionData = {
                    value: option.value,
                    text: option.text,
                    customerId: customerId
                };

                allAccountOptions.push(optionData);

                if (!accountsByCustomer[customerId]) {
                    accountsByCustomer[customerId] = [];
                }
                accountsByCustomer[customerId].push(optionData);
            }
        });

        // Function to filter accounts based on selected customer
        function filterAccounts() {
            const selectedCustomerId = customerSelect.value;

            // Clear current account options (except placeholder)
            while (accountSelect.options.length > 1) {
                accountSelect.remove(1);
            }

            // Reset account selection
            accountSelect.value = '';

            if (!selectedCustomerId) {
                // If no customer selected, show all accounts
                allAccountOptions.forEach(account => {
                    const option = new Option(account.text, account.value);
                    option.dataset.customerId = account.customerId;
                    accountSelect.add(option);
                });
            } else {
                // Show only accounts for selected customer
                const customerAccounts = accountsByCustomer[selectedCustomerId] || [];
                customerAccounts.forEach(account => {
                    const option = new Option(account.text, account.value);
                    option.dataset.customerId = account.customerId;
                    accountSelect.add(option);
                });

                // If customer has only one account, auto-select it
                if (customerAccounts.length === 1) {
                    accountSelect.value = customerAccounts[0].value;
                }
            }
        }

        // Function to filter customers based on selected account
        function filterCustomers() {
            const selectedAccountId = accountSelect.value;

            if (selectedAccountId) {
                // Find the customer ID for this account
                const account = allAccountOptions.find(acc => acc.value === selectedAccountId);
                if (account && account.customerId) {
                    // Auto-select the customer
                    customerSelect.value = account.customerId;
                }
            }
        }

        // Listen to customer selection changes
        customerSelect.addEventListener('change', function() {
            filterAccounts();
        });

        // Listen to account selection changes
        accountSelect.addEventListener('change', function() {
            if (accountSelect.value) {
                filterCustomers();
            }
        });

        // Initial filter on page load (in case of pre-filled values)
        if (customerSelect.value) {
            filterAccounts();
        }
    });
})();
