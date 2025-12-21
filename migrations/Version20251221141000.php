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
        $db = (string) $this->connection->getDatabase();
        $colExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration' AND COLUMN_NAME = 'code'",
            [$db]
        );
        if ($colExists === 0) {
            $this->addSql("ALTER TABLE driver_integration ADD code VARCHAR(64) DEFAULT NULL");
        }
        $this->addSql("UPDATE driver_integration SET code = CONCAT('integration_', id) WHERE code IS NULL");
        $this->addSql("ALTER TABLE driver_integration MODIFY code VARCHAR(64) NOT NULL");
        $idxExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration' AND INDEX_NAME = 'UNIQ_DRIVER_INTEGRATION_CODE'",
            [$db]
        );
        if ($idxExists === 0) {
            $this->addSql("CREATE UNIQUE INDEX UNIQ_DRIVER_INTEGRATION_CODE ON driver_integration (code)");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $idxExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration' AND INDEX_NAME = 'UNIQ_DRIVER_INTEGRATION_CODE'",
            [$db]
        );
        if ($idxExists === 1) {
            $this->addSql("DROP INDEX UNIQ_DRIVER_INTEGRATION_CODE ON driver_integration");
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
