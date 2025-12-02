<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202231500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add driver_step_completed and vehicle_step_completed flags to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user ADD driver_step_completed TINYINT(1) NOT NULL DEFAULT 0, ADD vehicle_step_completed TINYINT(1) NOT NULL DEFAULT 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP driver_step_completed, DROP vehicle_step_completed');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}