<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds customer address support
 *
 * Creates customer_address table to store permanent residence and correspondence addresses.
 */
final class Version20251202000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add customer_address table for permanent residence and correspondence addresses';
    }

    public function up(Schema $schema): void
    {
        // Create customer_address table
        $this->addSql('
            CREATE TABLE customer_address (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                customer_id UUID NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
                type TEXT NOT NULL CHECK (type IN (\'PERMANENT_RESIDENCE\', \'CORRESPONDENCE\')),
                street TEXT NOT NULL CHECK (char_length(street) BETWEEN 3 AND 100),
                city TEXT NOT NULL CHECK (char_length(city) BETWEEN 2 AND 50),
                postal_code TEXT NOT NULL CHECK (postal_code ~ \'^[0-9]{2}-[0-9]{3}$\'),
                country TEXT NOT NULL CHECK (char_length(country) BETWEEN 2 AND 50),
                UNIQUE(customer_id, type) DEFERRABLE INITIALLY DEFERRED
            )
        ');

        // Create indexes for better query performance
        $this->addSql('CREATE INDEX idx_customer_address_customer_id ON customer_address(customer_id)');
        $this->addSql('CREATE INDEX idx_customer_address_type ON customer_address(type)');
    }

    public function down(Schema $schema): void
    {
        // Drop indexes first
        $this->addSql('DROP INDEX IF EXISTS idx_customer_address_type');
        $this->addSql('DROP INDEX IF EXISTS idx_customer_address_customer_id');

        // Drop table
        $this->addSql('DROP TABLE IF EXISTS customer_address');
    }
}
