<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260113104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Bolt fields to driver_integration and create driver_integration_account table';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $hasCid = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration' AND COLUMN_NAME = 'bolt_customer_id'",
            [$db]
        );
        if ($hasCid === 0) {
            $this->addSql("ALTER TABLE driver_integration ADD bolt_customer_id VARCHAR(128) DEFAULT NULL");
        }
        $hasSecret = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration' AND COLUMN_NAME = 'bolt_customer_secret'",
            [$db]
        );
        if ($hasSecret === 0) {
            $this->addSql("ALTER TABLE driver_integration ADD bolt_customer_secret VARCHAR(255) DEFAULT NULL");
        }
        $hasCompanies = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration' AND COLUMN_NAME = 'bolt_company_ids'",
            [$db]
        );
        if ($hasCompanies === 0) {
            $this->addSql("ALTER TABLE driver_integration ADD bolt_company_ids JSON DEFAULT (JSON_ARRAY())");
        }

        $tableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration_account'",
            [$db]
        );
        if ($tableExists === 0) {
            $this->addSql("
                CREATE TABLE driver_integration_account (
                    id INT AUTO_INCREMENT NOT NULL,
                    driver_id INT NOT NULL,
                    integration_id INT NOT NULL,
                    id_driver VARCHAR(128) NOT NULL,
                    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                    INDEX IDX_DIA_DRIVER (driver_id),
                    INDEX IDX_DIA_INTEGRATION (integration_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            ");
            $this->addSql("ALTER TABLE driver_integration_account ADD CONSTRAINT FK_DIA_DRIVER FOREIGN KEY (driver_id) REFERENCES driver (id)");
            $this->addSql("ALTER TABLE driver_integration_account ADD CONSTRAINT FK_DIA_INTEGRATION FOREIGN KEY (integration_id) REFERENCES driver_integration (id)");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $hasCid = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration' AND COLUMN_NAME = 'bolt_customer_id'",
            [$db]
        );
        if ($hasCid === 1) {
            $this->addSql("ALTER TABLE driver_integration DROP COLUMN bolt_customer_id");
        }
        $hasSecret = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration' AND COLUMN_NAME = 'bolt_customer_secret'",
            [$db]
        );
        if ($hasSecret === 1) {
            $this->addSql("ALTER TABLE driver_integration DROP COLUMN bolt_customer_secret");
        }
        $hasCompanies = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration' AND COLUMN_NAME = 'bolt_company_ids'",
            [$db]
        );
        if ($hasCompanies === 1) {
            $this->addSql("ALTER TABLE driver_integration DROP COLUMN bolt_company_ids");
        }
        $tableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration_account'",
            [$db]
        );
        if ($tableExists === 1) {
            $this->addSql("ALTER TABLE driver_integration_account DROP FOREIGN KEY FK_DIA_DRIVER");
            $this->addSql("ALTER TABLE driver_integration_account DROP FOREIGN KEY FK_DIA_INTEGRATION");
            $this->addSql("DROP TABLE driver_integration_account");
        }
    }

    public function isTransactional(): bool
    {
        return true;
    }
}
