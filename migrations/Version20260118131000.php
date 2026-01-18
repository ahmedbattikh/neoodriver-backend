<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260118131000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure payment_method has default CB and fix empty values';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $colExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payment_operation' AND COLUMN_NAME = 'payment_method'",
            [$db]
        );
        if ($colExists === 1) {
            $this->addSql("UPDATE payment_operation SET payment_method = 'CB' WHERE payment_method IS NULL OR payment_method = ''");
            $this->addSql("ALTER TABLE payment_operation MODIFY COLUMN payment_method VARCHAR(8) NOT NULL DEFAULT 'CB'");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $colExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payment_operation' AND COLUMN_NAME = 'payment_method'",
            [$db]
        );
        if ($colExists === 1) {
            $this->addSql("ALTER TABLE payment_operation MODIFY COLUMN payment_method VARCHAR(8) NOT NULL");
        }
    }

    public function isTransactional(): bool
    {
        return true;
    }
}

