<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251221134500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create payment_operation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE payment_operation (
            id BIGINT AUTO_INCREMENT NOT NULL,
            driver_id INT NOT NULL,
            integration_code VARCHAR(50) NOT NULL,
            operation_type VARCHAR(50) NOT NULL,
            direction VARCHAR(10) NOT NULL,
            amount NUMERIC(12, 3) NOT NULL,
            currency VARCHAR(3) NOT NULL DEFAULT 'TND',
            status VARCHAR(20) NOT NULL,
            external_reference VARCHAR(100) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            occurred_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX IDX_PAYMENT_OPERATION_DRIVER_ID (driver_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("ALTER TABLE payment_operation ADD CONSTRAINT FK_PAYMENT_OPERATION_DRIVER FOREIGN KEY (driver_id) REFERENCES driver (id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE payment_operation DROP FOREIGN KEY FK_PAYMENT_OPERATION_DRIVER");
        $this->addSql("DROP TABLE payment_operation");
    }

    public function isTransactional(): bool
    {
        return false;
    }
}

