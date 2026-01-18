<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260118132500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bolt_scope to driver_integration';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $colExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration' AND COLUMN_NAME = 'bolt_scope'",
            [$db]
        );
        if ($colExists === 0) {
            $this->addSql("ALTER TABLE driver_integration ADD bolt_scope VARCHAR(128) DEFAULT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $colExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver_integration' AND COLUMN_NAME = 'bolt_scope'",
            [$db]
        );
        if ($colExists === 1) {
            $this->addSql("ALTER TABLE driver_integration DROP COLUMN bolt_scope");
        }
    }

    public function isTransactional(): bool
    {
        return true;
    }
}

