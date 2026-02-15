<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add taux_urssaf to neoo_config';
    }

    public function up(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $columnExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'neoo_config' AND COLUMN_NAME = 'taux_urssaf'",
            [$db]
        );
        if ($columnExists === 0) {
            $this->addSql("ALTER TABLE neoo_config ADD taux_urssaf NUMERIC(12,3) NOT NULL DEFAULT 0");
        }
    }

    public function down(Schema $schema): void
    {
        $db = (string) $this->connection->getDatabase();
        $columnExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'neoo_config' AND COLUMN_NAME = 'taux_urssaf'",
            [$db]
        );
        if ($columnExists === 1) {
            $this->addSql("ALTER TABLE neoo_config DROP COLUMN taux_urssaf");
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
