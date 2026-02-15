<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260202131500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter neoo_fee.start and neoo_fee.end from DATE to DOUBLE (float)';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $startType = (string) $this->connection->fetchOne(
            "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'neoo_fee' AND COLUMN_NAME = 'start'",
            [$db]
        );
        $endType = (string) $this->connection->fetchOne(
            "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'neoo_fee' AND COLUMN_NAME = 'end'",
            [$db]
        );
        if (strtolower($startType) === 'date') {
            $this->addSql("ALTER TABLE neoo_fee MODIFY start DOUBLE NOT NULL");
        }
        if (strtolower($endType) === 'date') {
            $this->addSql("ALTER TABLE neoo_fee MODIFY end DOUBLE NOT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $startType = (string) $this->connection->fetchOne(
            "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'neoo_fee' AND COLUMN_NAME = 'start'",
            [$db]
        );
        $endType = (string) $this->connection->fetchOne(
            "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'neoo_fee' AND COLUMN_NAME = 'end'",
            [$db]
        );
        if (strtolower($startType) !== 'date') {
            $this->addSql("ALTER TABLE neoo_fee MODIFY start DATE NOT NULL");
        }
        if (strtolower($endType) !== 'date') {
            $this->addSql("ALTER TABLE neoo_fee MODIFY end DATE NOT NULL");
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
