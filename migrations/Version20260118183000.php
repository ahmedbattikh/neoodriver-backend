<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260118183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create goals table and add class_driver column to driver';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();

        $goalsExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'goals'",
            [$db]
        );
        if ($goalsExists === 0) {
            $this->addSql("
                CREATE TABLE goals (
                    id INT AUTO_INCREMENT NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    amount NUMERIC(12,3) NOT NULL,
                    frequency VARCHAR(20) NOT NULL,
                    target_classes JSON DEFAULT NULL,
                    enabled TINYINT(1) NOT NULL DEFAULT 1,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            ");
        }

        $driverClassColExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver' AND COLUMN_NAME = 'class_driver'",
            [$db]
        );
        if ($driverClassColExists === 0) {
            $this->addSql("ALTER TABLE driver ADD class_driver VARCHAR(16) NOT NULL DEFAULT 'class1'");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();

        $goalsExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'goals'",
            [$db]
        );
        if ($goalsExists === 1) {
            $this->addSql("DROP TABLE goals");
        }

        $driverClassColExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver' AND COLUMN_NAME = 'class_driver'",
            [$db]
        );
        if ($driverClassColExists === 1) {
            $this->addSql("ALTER TABLE driver DROP COLUMN class_driver");
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}

