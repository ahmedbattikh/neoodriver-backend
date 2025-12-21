<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251221141000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add code column to driver_integration and ensure uniqueness';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE driver_integration ADD code VARCHAR(64) DEFAULT NULL");
        $this->addSql("UPDATE driver_integration SET code = CONCAT('integration_', id) WHERE code IS NULL");
        $this->addSql("ALTER TABLE driver_integration MODIFY code VARCHAR(64) NOT NULL");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_DRIVER_INTEGRATION_CODE ON driver_integration (code)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX UNIQ_DRIVER_INTEGRATION_CODE ON driver_integration");
        $this->addSql("ALTER TABLE driver_integration DROP COLUMN code");
    }

    public function isTransactional(): bool
    {
        return false;
    }
}

