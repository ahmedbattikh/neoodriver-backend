<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set EUR as payment_operation currency default and normalize existing rows';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE payment_operation CHANGE currency currency VARCHAR(3) DEFAULT 'EUR' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE payment_operation CHANGE currency currency VARCHAR(3) DEFAULT 'TND' NOT NULL");
    }
}
