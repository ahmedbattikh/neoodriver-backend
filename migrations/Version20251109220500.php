<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create login_otps table for email OTP login.
 */
final class Version20251109220500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create login_otps table for storing email OTP codes';
    }

    public function up(Schema $schema): void
    {
        // MySQL table
        $this->addSql('CREATE TABLE login_otps (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            code_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            consumed_at DATETIME DEFAULT NULL,
            attempts INT NOT NULL,
            ip_address VARCHAR(64) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_LOGIN_OTPS_EMAIL (email),
            INDEX IDX_LOGIN_OTPS_EXPIRES_AT (expires_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE login_otps');
    }
}