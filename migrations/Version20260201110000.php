<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260201110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add valide (nullable boolean) column to expense_note table';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $colExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'expense_note' AND COLUMN_NAME = 'valide'",
            [$db]
        );
        if ($colExists === 0) {
            $this->addSql("ALTER TABLE expense_note ADD valide TINYINT(1) DEFAULT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $colExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'expense_note' AND COLUMN_NAME = 'valide'",
            [$db]
        );
        if ($colExists === 1) {
            $this->addSql("ALTER TABLE expense_note DROP COLUMN valide");
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
