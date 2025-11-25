<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251117191500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pic_profile_id to user referencing attachment(id)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD pic_profile_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_USER_PIC_PROFILE FOREIGN KEY (pic_profile_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_USER_PIC_PROFILE ON user (pic_profile_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_USER_PIC_PROFILE ON user');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_USER_PIC_PROFILE');
        $this->addSql('ALTER TABLE user DROP pic_profile_id');
    }
}