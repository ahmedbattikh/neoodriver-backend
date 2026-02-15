<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260201123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ride_distance decimal column to payment_operation';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $colExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payment_operation' AND COLUMN_NAME = 'ride_distance'",
            [$db]
        );
        if ($colExists === 0) {
            $this->addSql("ALTER TABLE payment_operation ADD ride_distance DECIMAL(12,3) DEFAULT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $colExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payment_operation' AND COLUMN_NAME = 'ride_distance'",
            [$db]
        );
        if ($colExists === 1) {
            $this->addSql("ALTER TABLE payment_operation DROP COLUMN ride_distance");
        }
    }

    public function isTransactional(): bool
    {
        return true;
    }
}
