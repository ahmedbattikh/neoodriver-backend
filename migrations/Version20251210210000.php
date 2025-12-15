<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251210210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add verified flag to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user ADD verified TINYINT(1) NOT NULL DEFAULT 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user DROP verified");
    }

    public function isTransactional(): bool
    {
        return false;
    }
}

