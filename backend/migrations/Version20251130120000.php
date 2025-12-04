<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add support for interbank transfers with blocked amounts
 *
 * - Adds blocked_amount column to bank_account table
 * - Creates pending_interbank_transfer table for tracking external transfers
 */
final class Version20251130120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add interbank transfer support with blocked amounts and pending transfers table';
    }

    public function up(Schema $schema): void
    {
        // Add blocked_amount column to bank_account table
        $this->addSql('
            ALTER TABLE bank_account
            ADD COLUMN blocked_amount BIGINT NOT NULL DEFAULT 0 CHECK (blocked_amount >= 0)
        ');

        // Create pending_interbank_transfer table
        $this->addSql('
            CREATE TABLE pending_interbank_transfer (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                from_bank_account_id UUID NOT NULL REFERENCES bank_account(id) ON DELETE RESTRICT,
                to_iban TEXT NOT NULL CHECK (to_iban ~ \'^[A-Z]{2}[0-9]{2}[A-Z0-9]+$\'),
                amount BIGINT NOT NULL CHECK (amount > 0),
                currency TEXT NOT NULL CHECK (currency IN (\'PLN\', \'EUR\', \'USD\', \'GBP\')),
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                is_processed BOOLEAN NOT NULL DEFAULT FALSE,
                processed_at TIMESTAMPTZ,
                transaction_id UUID
            )
        ');

        // Add indexes for better query performance
        $this->addSql('CREATE INDEX idx_pending_interbank_transfer_from_bank_account_id ON pending_interbank_transfer(from_bank_account_id)');
        $this->addSql('CREATE INDEX idx_pending_interbank_transfer_is_processed ON pending_interbank_transfer(is_processed) WHERE is_processed = false');
        $this->addSql('CREATE INDEX idx_pending_interbank_transfer_created_at ON pending_interbank_transfer(created_at DESC)');

        // Add status column to transaction table
        $this->addSql('
            ALTER TABLE transaction
            ADD COLUMN status TEXT NOT NULL DEFAULT \'EXECUTED\' CHECK (status IN (\'ORDERED\', \'EXECUTED\', \'CANCELED\'))
        ');

        // Update transaction type constraint to include INTERBANK_WITHDRAWAL
        $this->addSql('
            ALTER TABLE transaction
            DROP CONSTRAINT IF EXISTS transaction_type_check
        ');

        // Add index for status
        $this->addSql('CREATE INDEX idx_transaction_status ON transaction(status) WHERE status = \'ORDERED\'');
    }

    public function down(Schema $schema): void
    {
        // Drop indexes
        $this->addSql('DROP INDEX IF EXISTS idx_pending_interbank_transfer_created_at');
        $this->addSql('DROP INDEX IF EXISTS idx_pending_interbank_transfer_is_processed');
        $this->addSql('DROP INDEX IF EXISTS idx_pending_interbank_transfer_from_bank_account_id');

        // Drop pending_interbank_transfer table
        $this->addSql('DROP TABLE IF EXISTS pending_interbank_transfer');

        // Remove blocked_amount column from bank_account table
        $this->addSql('ALTER TABLE bank_account DROP COLUMN IF EXISTS blocked_amount');

        // Drop transaction status index and column
        $this->addSql('DROP INDEX IF EXISTS idx_transaction_status');
        $this->addSql('ALTER TABLE transaction DROP COLUMN IF EXISTS status');
    }
}
