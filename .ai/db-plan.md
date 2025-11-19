# Easy Banking System Database Schema
## 1. Tables
### 1.1. user
- id: UUID PRIMARY KEY
- username: TEXT NOT NULL UNIQUE
- password: TEXT NOT NULL
- first_name: TEXT NOT NULL
- last_name: TEXT NOT NULL
- is_active: BOOLEAN NOT NULL
- role: TEXT NOT NULL CHECK (role IN ('EMPLOEE', 'CUSTOMER'))

### 1.2. bank_account
- id: UUID PRIMARY KEY
- iban: TEXT NOT NULL UNIQUE
- is_active: BOOLEAN NOT NULL
- balance: INT NOT NULL DEFAULT 0
- currency: TEXT NOT NULL CHECK (currency IN ('PLN', 'EUR'))
- customer_id: UUID NOT NULL REFERENCES user(id)

### 1.3. transaction
- id: UUID PRIMARY KEY
- type: TEXT NOT NULL CHECK (type IN ('TRANSFER_WITHDRAWAL', 'TRANSFER_DEPOSIT', 'CASH_WITHDRAWAL'))
- amount: INT NOT NULL
- currency: TEXT NOT NULL CHECK (currency IN ('PLN', 'EUR'))
- original_amount: INT NOT NULL
- original_currency: TEXT NOT NULL CHECK (original_currency IN ('PLN', 'EUR'))
- exchange_rate: DECIMAL(10, 6) NOT NULL
- bank_account_id: UUID NOT NULL REFERENCES bank_account(id)
- occured_at: TIMESTAMPTZ NOT NULL DEFAULT now()

## 2. Relations
- One customer (user) has many bank accounts (bank_account).
- One bank account (bank_account) has many transactions (transaction).

## 3. Indexes
- Index on column `customer_id` in bank_account table.
- Index on column `bank_account_id` in transaction table.
