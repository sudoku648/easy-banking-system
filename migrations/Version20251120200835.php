<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add locale column to user table for storing user's preferred language
 */
final class Version20251120200835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add locale column to user table';
    }

    public function up(Schema $schema): void
    {
        // Add locale column with default value 'pl' (Polish)
        // Note: When adding new locales, create a new migration to update the CHECK constraint
        $this->addSql('
            ALTER TABLE "user"
            ADD COLUMN locale TEXT NOT NULL DEFAULT \'pl\'
            CHECK (locale IN (\'pl\', \'en\'))
        ');
    }

    public function down(Schema $schema): void
    {
        // Remove locale column
        $this->addSql('ALTER TABLE "user" DROP COLUMN locale');
    }
}
