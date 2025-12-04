<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add debit_card table for storing debit cards associated with bank accounts
 */
final class Version20251127120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add debit_card table for ATM withdrawals';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE debit_card (
                id UUID PRIMARY KEY,
                card_number TEXT NOT NULL UNIQUE,
                bank_account_id UUID NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                blocked_at TIMESTAMP DEFAULT NULL,
                CONSTRAINT fk_debit_card_bank_account
                    FOREIGN KEY (bank_account_id)
                    REFERENCES bank_account(id)
                    ON DELETE RESTRICT,
                CONSTRAINT chk_card_number_length
                    CHECK (LENGTH(card_number) = 16),
                CONSTRAINT chk_card_number_digits
                    CHECK (card_number ~ \'^\d{16}$\')
            )
        ');

        // Create index for faster lookups
        $this->addSql('CREATE INDEX idx_debit_card_bank_account ON debit_card(bank_account_id)');
        $this->addSql('CREATE INDEX idx_debit_card_active ON debit_card(is_active) WHERE is_active = TRUE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS debit_card');
    }
}
