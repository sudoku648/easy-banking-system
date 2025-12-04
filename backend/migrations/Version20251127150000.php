<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add ATM_WITHDRAWAL transaction type to transaction_type_check constraint
 */
final class Version20251127150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ATM_WITHDRAWAL to allowed transaction types';
    }

    public function up(Schema $schema): void
    {
        // Drop the existing constraint
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT transaction_type_check');

        // Add the new constraint with ATM_WITHDRAWAL included
        $this->addSql('
            ALTER TABLE transaction
            ADD CONSTRAINT transaction_type_check
            CHECK (type IN (\'TRANSFER_WITHDRAWAL\', \'TRANSFER_DEPOSIT\', \'CASH_WITHDRAWAL\', \'CASH_DEPOSIT\', \'ATM_WITHDRAWAL\'))
        ');
    }

    public function down(Schema $schema): void
    {
        // Drop the constraint with ATM_WITHDRAWAL
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT transaction_type_check');

        // Restore the original constraint without ATM_WITHDRAWAL
        $this->addSql('
            ALTER TABLE transaction
            ADD CONSTRAINT transaction_type_check
            CHECK (type IN (\'TRANSFER_WITHDRAWAL\', \'TRANSFER_DEPOSIT\', \'CASH_WITHDRAWAL\', \'CASH_DEPOSIT\'))
        ');
    }
}
