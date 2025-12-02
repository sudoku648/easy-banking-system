<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add USD and GBP currencies support
 */
final class Version20251130000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add USD and GBP currencies to bank_account and transaction tables';
    }

    public function up(Schema $schema): void
    {
        // Update bank_account table to support USD and GBP
        $this->addSql('
            ALTER TABLE bank_account DROP CONSTRAINT IF EXISTS bank_account_currency_check
        ');
        $this->addSql('
            ALTER TABLE bank_account ADD CONSTRAINT bank_account_currency_check
            CHECK (currency IN (\'PLN\', \'EUR\', \'USD\', \'GBP\'))
        ');

        // Update transaction table to support USD and GBP
        $this->addSql('
            ALTER TABLE transaction DROP CONSTRAINT IF EXISTS transaction_currency_check
        ');
        $this->addSql('
            ALTER TABLE transaction ADD CONSTRAINT transaction_currency_check
            CHECK (currency IN (\'PLN\', \'EUR\', \'USD\', \'GBP\'))
        ');

        $this->addSql('
            ALTER TABLE transaction DROP CONSTRAINT IF EXISTS transaction_original_currency_check
        ');
        $this->addSql('
            ALTER TABLE transaction ADD CONSTRAINT transaction_original_currency_check
            CHECK (original_currency IN (\'PLN\', \'EUR\', \'USD\', \'GBP\'))
        ');
    }

    public function down(Schema $schema): void
    {
        // Revert to only PLN and EUR support
        $this->addSql('
            ALTER TABLE bank_account DROP CONSTRAINT IF EXISTS bank_account_currency_check
        ');
        $this->addSql('
            ALTER TABLE bank_account ADD CONSTRAINT bank_account_currency_check
            CHECK (currency IN (\'PLN\', \'EUR\'))
        ');

        $this->addSql('
            ALTER TABLE transaction DROP CONSTRAINT IF EXISTS transaction_currency_check
        ');
        $this->addSql('
            ALTER TABLE transaction ADD CONSTRAINT transaction_currency_check
            CHECK (currency IN (\'PLN\', \'EUR\'))
        ');

        $this->addSql('
            ALTER TABLE transaction DROP CONSTRAINT IF EXISTS transaction_original_currency_check
        ');
        $this->addSql('
            ALTER TABLE transaction ADD CONSTRAINT transaction_original_currency_check
            CHECK (original_currency IN (\'PLN\', \'EUR\'))
        ');
    }
}
