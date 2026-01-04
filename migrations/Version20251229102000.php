<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251229102000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create expense_note table';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $tableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'expense_note'",
            [$db]
        );
        if ($tableExists === 0) {
            $this->addSql("
                CREATE TABLE expense_note (
                    id INT AUTO_INCREMENT NOT NULL,
                    driver_id INT NOT NULL,
                    invoice_id INT DEFAULT NULL,
                    note_date DATE NOT NULL,
                    amount_ttc NUMERIC(12,3) NOT NULL,
                    type VARCHAR(64) NOT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    INDEX IDX_EXPENSE_NOTE_DRIVER (driver_id),
                    INDEX IDX_EXPENSE_NOTE_INVOICE (invoice_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            ");
            $this->addSql("ALTER TABLE expense_note ADD CONSTRAINT FK_EXPENSE_NOTE_DRIVER FOREIGN KEY (driver_id) REFERENCES driver (id)");
            $this->addSql("ALTER TABLE expense_note ADD CONSTRAINT FK_EXPENSE_NOTE_INVOICE FOREIGN KEY (invoice_id) REFERENCES attachment (id)");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $tableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'expense_note'",
            [$db]
        );
        if ($tableExists === 1) {
            $this->addSql("DROP TABLE expense_note");
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}

