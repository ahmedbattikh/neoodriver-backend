<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create balance table for driver one-to-one with sold, sold_conge, total_debit, last_update';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $tableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'balance'",
            [$db]
        );
        if ($tableExists === 0) {
            $this->addSql("
                CREATE TABLE balance (
                    id INT AUTO_INCREMENT NOT NULL,
                    driver_id INT NOT NULL,
                    sold NUMERIC(12,3) NOT NULL,
                    sold_conge NUMERIC(12,3) NOT NULL,
                    total_debit NUMERIC(12,3) NOT NULL,
                    last_update DATETIME NOT NULL,
                    UNIQUE INDEX UNIQ_BALANCE_DRIVER (driver_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            ");
            $this->addSql("ALTER TABLE balance ADD CONSTRAINT FK_BALANCE_DRIVER FOREIGN KEY (driver_id) REFERENCES driver (id)");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $tableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'balance'",
            [$db]
        );
        if ($tableExists === 1) {
            $this->addSql("DROP TABLE balance");
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
