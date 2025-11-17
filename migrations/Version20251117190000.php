<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251117190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reference column to user and unique index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD reference VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_REFERENCE ON user (reference)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_USER_REFERENCE ON user');
        $this->addSql('ALTER TABLE user DROP reference');
    }
}