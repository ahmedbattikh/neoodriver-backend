<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260315120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create integration_sync_log table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS integration_sync_log (
                id INT AUTO_INCREMENT NOT NULL,
                status VARCHAR(16) NOT NULL,
                start_at DATETIME NOT NULL,
                end_at DATETIME NOT NULL,
                hours INT NOT NULL,
                started_at DATETIME NOT NULL,
                finished_at DATETIME DEFAULT NULL,
                total_ops INT DEFAULT NULL,
                synced_accounts INT DEFAULT NULL,
                accounts_total INT DEFAULT NULL,
                integrations_total INT DEFAULT NULL,
                error_message LONGTEXT DEFAULT NULL,
                report JSON DEFAULT NULL,
                trigger_type VARCHAR(32) NOT NULL,
                retry_of_log_id INT DEFAULT NULL,
                INDEX IDX_INT_SYNC_STATUS (status),
                INDEX IDX_INT_SYNC_STARTED_AT (started_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS integration_sync_log");
    }
}
