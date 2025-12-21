<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251221140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create payment_batch table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE payment_batch (
            id INT AUTO_INCREMENT NOT NULL,
            integration_id INT NOT NULL,
            period_start DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
            period_end DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
            total_amount NUMERIC(12, 3) NOT NULL,
            INDEX IDX_PAYMENT_BATCH_INTEGRATION_ID (integration_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("ALTER TABLE payment_batch ADD CONSTRAINT FK_PAYMENT_BATCH_INTEGRATION FOREIGN KEY (integration_id) REFERENCES driver_integration (id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE payment_batch DROP FOREIGN KEY FK_PAYMENT_BATCH_INTEGRATION");
        $this->addSql("DROP TABLE payment_batch");
    }

    public function isTransactional(): bool
    {
        return false;
    }
}

