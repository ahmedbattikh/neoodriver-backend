<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260113094000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment_method, bonus, tips to payment_operation';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $hasMethod = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payment_operation' AND COLUMN_NAME = 'payment_method'",
            [$db]
        );
        if ($hasMethod === 0) {
            $this->addSql("ALTER TABLE payment_operation ADD payment_method VARCHAR(8) NOT NULL DEFAULT 'CB'");
        }
        $hasBonus = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payment_operation' AND COLUMN_NAME = 'bonus'",
            [$db]
        );
        if ($hasBonus === 0) {
            $this->addSql("ALTER TABLE payment_operation ADD bonus NUMERIC(12, 3) NOT NULL DEFAULT '0.000'");
        }
        $hasTips = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payment_operation' AND COLUMN_NAME = 'tips'",
            [$db]
        );
        if ($hasTips === 0) {
            $this->addSql("ALTER TABLE payment_operation ADD tips NUMERIC(12, 3) NOT NULL DEFAULT '0.000'");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $hasMethod = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payment_operation' AND COLUMN_NAME = 'payment_method'",
            [$db]
        );
        if ($hasMethod === 1) {
            $this->addSql("ALTER TABLE payment_operation DROP COLUMN payment_method");
        }
        $hasBonus = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payment_operation' AND COLUMN_NAME = 'bonus'",
            [$db]
        );
        if ($hasBonus === 1) {
            $this->addSql("ALTER TABLE payment_operation DROP COLUMN bonus");
        }
        $hasTips = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payment_operation' AND COLUMN_NAME = 'tips'",
            [$db]
        );
        if ($hasTips === 1) {
            $this->addSql("ALTER TABLE payment_operation DROP COLUMN tips");
        }
    }

    public function isTransactional(): bool
    {
        return true;
    }
}
