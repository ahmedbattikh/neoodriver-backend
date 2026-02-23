<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create conge_request table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS conge_request (
                id INT AUTO_INCREMENT NOT NULL,
                driver_id INT NOT NULL,
                amount NUMERIC(12,3) NOT NULL,
                status VARCHAR(16) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                admin_note LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                approved_at DATETIME DEFAULT NULL,
                INDEX IDX_CONGE_REQ_DRIVER (driver_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");
        $this->addSql("ALTER TABLE conge_request ADD CONSTRAINT FK_CONGE_REQ_DRIVER FOREIGN KEY (driver_id) REFERENCES driver (id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS conge_request");
    }
}
