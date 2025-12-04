import axios from 'axios';

const API_BASE_URL = process.env.API_BASE_URL || 'http://localhost:8080/api';

/**
 * API client for test setup and teardown
 */
class ApiClient {
  constructor() {
    this.client = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json',
      },
    });
  }

  /**
   * Create a customer via API (for test setup)
   * Note: This might need to be done via CLI command if API doesn't exist
   */
  async createCustomer(data) {
    // This is a placeholder - adjust based on actual API
    // For now, we'll assume employees create customers through the UI
    return {
      username: data.username,
      firstName: data.firstName,
      lastName: data.lastName,
    };
  }

  /**
   * Create an employee via CLI command
   */
  async createEmployee(data) {
    // Employees are created via CLI in this system
    // We'll need to execute the CLI command
    const { execSync } = require('child_process');
    
    try {
      execSync(
        `docker exec easy-banking-service-ebs php bin/console app:user:create-employee ${data.username} ${data.password} "${data.firstName}" "${data.lastName}"`,
        { encoding: 'utf-8' }
      );
      
      return {
        username: data.username,
        firstName: data.firstName,
        lastName: data.lastName,
      };
    } catch (error) {
      console.error('Failed to create employee:', error.message);
      throw error;
    }
  }

  /**
   * Login and get auth token
   */
  async login(username, password) {
    const response = await this.client.post('/auth/login', {
      username,
      password,
    });
    return response.data;
  }

  /**
   * Create bank account
   */
  async createBankAccount(customerId, currency, token) {
    const response = await this.client.post(
      '/employee/bank-account/open',
      { customerId, currency },
      { headers: { Authorization: `Bearer ${token}` } }
    );
    return response.data;
  }

  /**
   * Get customer accounts
   */
  async getCustomerAccounts(token) {
    const response = await this.client.get('/customer/accounts', {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  }

  /**
   * Deposit money
   */
  async depositMoney(bankAccountId, amount, token) {
    const response = await this.client.post(
      '/employee/transaction/deposit',
      { bankAccountId, amount },
      { headers: { Authorization: `Bearer ${token}` } }
    );
    return response.data;
  }
}

export const apiClient = new ApiClient();
