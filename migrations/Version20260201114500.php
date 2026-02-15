<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260201114500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create neoo_config table';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $tableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'neoo_config'",
            [$db]
        );
        if ($tableExists === 0) {
            $this->addSql("
                CREATE TABLE neoo_config (
                    id INT AUTO_INCREMENT NOT NULL,
                    fix_neoo_monthly NUMERIC(12,3) NOT NULL DEFAULT 0,
                    taux_conge NUMERIC(12,3) NOT NULL DEFAULT 0,
                    frais_km NUMERIC(12,3) NOT NULL DEFAULT 0,
                    taux_pas NUMERIC(12,3) NOT NULL DEFAULT 0,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            ");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $tableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'neoo_config'",
            [$db]
        );
        if ($tableExists === 1) {
            $this->addSql("DROP TABLE neoo_config");
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
