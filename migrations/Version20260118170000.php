<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260118170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add original_object JSON column to payment_operation';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $colExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payment_operation' AND COLUMN_NAME = 'original_object'",
            [$db]
        );
        if ($colExists === 0) {
            $this->addSql("ALTER TABLE payment_operation ADD original_object JSON DEFAULT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $colExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payment_operation' AND COLUMN_NAME = 'original_object'",
            [$db]
        );
        if ($colExists === 1) {
            $this->addSql("ALTER TABLE payment_operation DROP COLUMN original_object");
        }
    }

    public function isTransactional(): bool
    {
        return true;
    }
}
