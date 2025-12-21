<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251221130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create driver_integration table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE driver_integration (
            id INT AUTO_INCREMENT NOT NULL,
            code VARCHAR(64) NOT NULL,
            logo_path VARCHAR(255) DEFAULT NULL,
            name VARCHAR(128) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            enabled TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            UNIQUE INDEX UNIQ_DRIVER_INTEGRATION_CODE (code),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE driver_integration");
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
