<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial database schema for Easy Banking System
 * 
 * Creates tables for users (employees and customers), bank accounts, and transactions.
 */
final class Version20251119111842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial database schema: users, bank_accounts, and transactions tables';
    }

    public function up(Schema $schema): void
    {
        // Enable UUID extension
        $this->addSql('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');

        // Create user table (employees and customers)
        $this->addSql('
            CREATE TABLE "user" (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                username TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                first_name TEXT NOT NULL CHECK (char_length(first_name) BETWEEN 2 AND 50),
                last_name TEXT NOT NULL CHECK (char_length(last_name) BETWEEN 2 AND 50),
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                role TEXT NOT NULL CHECK (role IN (\'EMPLOYEE\', \'CUSTOMER\'))
            )
        ');

        // Create bank_account table
        $this->addSql('
            CREATE TABLE bank_account (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                iban TEXT NOT NULL UNIQUE CHECK (iban ~ \'^[A-Z]{2}[0-9]{2}[A-Z0-9]+$\'),
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                balance BIGINT NOT NULL DEFAULT 0 CHECK (balance >= 0),
                currency TEXT NOT NULL CHECK (currency IN (\'PLN\', \'EUR\')),
                customer_id UUID NOT NULL REFERENCES "user"(id) ON DELETE RESTRICT
            )
        ');

        // Create transaction table
        $this->addSql('
            CREATE TABLE transaction (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                type TEXT NOT NULL CHECK (type IN (\'TRANSFER_WITHDRAWAL\', \'TRANSFER_DEPOSIT\', \'CASH_WITHDRAWAL\')),
                amount BIGINT NOT NULL CHECK (amount > 0),
                currency TEXT NOT NULL CHECK (currency IN (\'PLN\', \'EUR\')),
                original_amount BIGINT NOT NULL CHECK (original_amount > 0),
                original_currency TEXT NOT NULL CHECK (original_currency IN (\'PLN\', \'EUR\')),
                exchange_rate DECIMAL(10, 6) NOT NULL CHECK (exchange_rate > 0),
                bank_account_id UUID NOT NULL REFERENCES bank_account(id) ON DELETE RESTRICT,
                occurred_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Create indexes for better query performance
        $this->addSql('CREATE INDEX idx_bank_account_customer_id ON bank_account(customer_id)');
        $this->addSql('CREATE INDEX idx_transaction_bank_account_id ON transaction(bank_account_id)');
        $this->addSql('CREATE INDEX idx_transaction_occurred_at ON transaction(occurred_at DESC)');
        $this->addSql('CREATE INDEX idx_user_username ON "user"(username)');
    }

    public function down(Schema $schema): void
    {
        // Drop indexes first
        $this->addSql('DROP INDEX IF EXISTS idx_user_username');
        $this->addSql('DROP INDEX IF EXISTS idx_transaction_occurred_at');
        $this->addSql('DROP INDEX IF EXISTS idx_transaction_bank_account_id');
        $this->addSql('DROP INDEX IF EXISTS idx_bank_account_customer_id');

        // Drop tables in reverse order (respecting foreign keys)
        $this->addSql('DROP TABLE IF EXISTS transaction');
        $this->addSql('DROP TABLE IF EXISTS bank_account');
        $this->addSql('DROP TABLE IF EXISTS "user"');

        // Note: We don't drop the uuid-ossp extension as it might be used by other schemas
    }
}
