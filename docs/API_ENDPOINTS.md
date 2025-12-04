# API Endpoints Documentation

## Overview
This document describes all API endpoints available for the React frontend application. All endpoints return JSON responses using standardized Response models.

## Authentication

### POST /api/auth/login
Login to the system.

**Request Body:**
```json
{
  "username": "string",
  "password": "string"
}
```

**Response (200):**
```json
{
  "message": "Login successful",
  "data": {
    "user": {
      "id": "uuid",
      "username": "string",
      "role": "CUSTOMER|EMPLOYEE",
      "firstName": "string",
      "lastName": "string"
    }
  }
}
```

### POST /api/auth/logout
Logout from the system.

**Response (200):**
```json
{
  "message": "Logout successful"
}
```

### GET /api/auth/me
Get current authenticated user information.

**Response (200):**
```json
{
  "message": "User retrieved successfully",
  "data": {
    "user": {
      "id": "uuid",
      "username": "string",
      "role": "CUSTOMER|EMPLOYEE",
      "firstName": "string",
      "lastName": "string"
    }
  }
}
```

## Customer Endpoints

All customer endpoints require authentication with `ROLE_CUSTOMER`.

### GET /api/customer/accounts
Get all bank accounts for the authenticated customer.

**Response (200):**
```json
{
  "message": "Accounts retrieved successfully",
  "data": {
    "accounts": [
      {
        "id": "uuid",
        "iban": "string",
        "balance": 0,
        "blockedAmount": 0,
        "availableBalance": 0,
        "currency": "PLN|USD|EUR",
        "isActive": true
      }
    ]
  }
}
```

### GET /api/customer/accounts/{accountId}/transactions
Get transaction history for a specific account.

**Response (200):**
```json
{
  "message": "Transactions retrieved successfully",
  "data": {
    "transactions": [
      {
        "id": "uuid",
        "type": "TRANSFER_WITHDRAWAL|TRANSFER_DEPOSIT|CASH_WITHDRAWAL|CASH_DEPOSIT|ATM_WITHDRAWAL",
        "amount": 0,
        "currency": "PLN|USD|EUR",
        "originalAmount": 0,
        "originalCurrency": "PLN|USD|EUR",
        "exchangeRate": 1.0,
        "status": "EXECUTED|ORDERED|CANCELED",
        "occurredAt": "2024-01-01 12:00:00"
      }
    ]
  }
}
```

**Error Responses:**
- **422 Unprocessable Entity:** Account not found or does not belong to the customer

### POST /api/customer/transfer
Transfer money to another account (internal or interbank).

**Request Body:**
```json
{
  "sourceAccountId": "uuid",
  "recipientIban": "string",
  "amount": 0,
  "title": "string"
}
```

**Response (200):**
```json
{
  "message": "Transfer completed successfully"
}
```
or
```json
{
  "message": "Interbank transfer initiated successfully"
}
```

**Error Responses:**
- **400 Bad Request:** Missing required fields
- **422 Unprocessable Entity:** Source account not found, insufficient funds, account closed, etc.
- **500 Internal Server Error:** Unexpected error

### POST /api/customer/change-password
Change password for the authenticated customer.

**Request Body:**
```json
{
  "currentPassword": "string",
  "newPassword": "string"
}
```

**Response (200):**
```json
{
  "message": "Password changed successfully"
}
```

**Error Responses:**
- **400 Bad Request:** Missing required fields
- **422 Unprocessable Entity:** Current password is incorrect, new password validation failed

### POST /api/customer/block-debit-card
Block an active debit card for an account.

**Request Body:**
```json
{
  "accountId": "uuid"
}
```

**Response (200):**
```json
{
  "message": "Debit card blocked successfully"
}
```

**Error Responses:**
- **400 Bad Request:** Missing accountId
- **422 Unprocessable Entity:** Account not found, no active debit card found

## Employee Endpoints

All employee endpoints require authentication with `ROLE_EMPLOYEE`.

### GET /api/employee/customers
Get list of all customers.

**Response (200):**
```json
{
  "message": "Customers retrieved successfully",
  "data": {
    "customers": [
      {
        "id": "uuid",
        "username": "string",
        "firstName": "string",
        "lastName": "string",
        "fullName": "string"
      }
    ]
  }
}
```

### GET /api/employee/customers/{customerId}
Get detailed information about a specific customer.

**Response (200):**
```json
{
  "message": "Customer retrieved successfully",
  "data": {
    "customer": {
      "id": "uuid",
      "username": "string",
      "firstName": "string",
      "lastName": "string",
      "permanentResidence": {
        "street": "string",
        "city": "string",
        "postalCode": "string",
        "country": "string"
      },
      "correspondenceAddresses": [
        {
          "street": "string",
          "city": "string",
          "postalCode": "string",
          "country": "string"
        }
      ]
    }
  }
}
```

**Error Responses:**
- **422 Unprocessable Entity:** Customer not found, user is not a customer

### GET /api/employee/customers/{customerId}/accounts
Get all bank accounts for a specific customer.

**Response (200):**
```json
{
  "message": "Accounts retrieved successfully",
  "data": {
    "accounts": [
      {
        "id": "uuid",
        "iban": "string",
        "balance": 0,
        "blockedAmount": 0,
        "availableBalance": 0,
        "currency": "PLN|USD|EUR",
        "isActive": true
      }
    ]
  }
}
```

### GET /api/employee/accounts/{accountId}/transactions
Get transaction history for a specific account.

**Response (200):**
```json
{
  "message": "Transactions retrieved successfully",
  "data": {
    "transactions": [
      {
        "id": "uuid",
        "type": "TRANSFER_WITHDRAWAL|TRANSFER_DEPOSIT|CASH_WITHDRAWAL|CASH_DEPOSIT|ATM_WITHDRAWAL",
        "amount": 0,
        "currency": "PLN|USD|EUR",
        "originalAmount": 0,
        "originalCurrency": "PLN|USD|EUR",
        "exchangeRate": 1.0,
        "status": "EXECUTED|ORDERED|CANCELED",
        "occurredAt": "2024-01-01 12:00:00"
      }
    ]
  }
}
```

### POST /api/employee/deposit
Deposit money to a customer account.

**Request Body:**
```json
{
  "accountId": "uuid",
  "amount": 0
}
```

**Response (200):**
```json
{
  "message": "Deposit completed successfully"
}
```

**Error Responses:**
- **400 Bad Request:** Missing required fields
- **422 Unprocessable Entity:** Account not found, account closed, invalid amount

### POST /api/employee/open-account-new-customer
Create a new customer and open a bank account.

**Request Body:**
```json
{
  "username": "string",
  "password": "string",
  "firstName": "string",
  "lastName": "string",
  "permanentResidence": {
    "street": "string",
    "city": "string",
    "postalCode": "string",
    "country": "string"
  },
  "correspondenceAddresses": [
    {
      "street": "string",
      "city": "string",
      "postalCode": "string",
      "country": "string"
    }
  ],
  "currency": "PLN|USD|EUR"
}
```

**Response (201):**
```json
{
  "message": "Customer created and account opened successfully",
  "data": {
    "customerId": "uuid",
    "accountId": "uuid"
  }
}
```

**Error Responses:**
- **400 Bad Request:** Missing required fields
- **422 Unprocessable Entity:** Username already exists, validation errors

### POST /api/employee/open-account-existing-customer
Open a new bank account for an existing customer.

**Request Body:**
```json
{
  "customerId": "uuid",
  "currency": "PLN|USD|EUR"
}
```

**Response (201):**
```json
{
  "message": "Account opened successfully",
  "data": {
    "accountId": "uuid"
  }
}
```

**Error Responses:**
- **400 Bad Request:** Missing required fields
- **422 Unprocessable Entity:** Customer not found

### POST /api/employee/close-account
Close a bank account.

**Request Body:**
```json
{
  "accountId": "uuid"
}
```

**Response (200):**
```json
{
  "message": "Account closed successfully"
}
```

**Error Responses:**
- **400 Bad Request:** Missing accountId
- **422 Unprocessable Entity:** Account not found, account has balance, account already closed

### POST /api/employee/issue-debit-card
Issue a new debit card for an account.

**Request Body:**
```json
{
  "accountId": "uuid"
}
```

**Response (201):**
```json
{
  "message": "Debit card issued successfully",
  "data": {
    "cardId": "uuid"
  }
}
```

**Error Responses:**
- **400 Bad Request:** Missing accountId
- **422 Unprocessable Entity:** Account not found, account closed, active card already exists

### POST /api/employee/block-debit-card
Block a debit card.

**Request Body:**
```json
{
  "cardId": "uuid"
}
```

**Response (200):**
```json
{
  "message": "Debit card blocked successfully"
}
```

**Error Responses:**
- **400 Bad Request:** Missing cardId
- **422 Unprocessable Entity:** Card not found, card already blocked

## Response Models

All responses follow standardized formats:

### Success Response (200/201)
```json
{
  "message": "string",
  "data": {
    // Optional response data
  }
}
```

### Bad Request (400)
```json
{
  "message": "string",
  "errors": {
    "field1": ["error1", "error2"],
    "field2": ["error1"]
  }
}
```

### Unprocessable Entity (422)
```json
{
  "message": "string"
}
```

### Internal Server Error (500)
```json
{
  "message": "An unexpected error occurred. Please try again later."
}
```

## Notes

- All amounts are in cents (e.g., 1000 = 10.00)
- All dates are in format: `Y-m-d H:i:s`
- UUIDs are in format: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`
- Authentication is handled via session cookies
- CORS is configured to allow frontend origin
