<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210190036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE driver_documents CHANGE vtc_card_valid vtc_card_valid VARCHAR(255) NOT NULL, CHANGE driving_license_valid driving_license_valid VARCHAR(255) NOT NULL, CHANGE identity_card_valid identity_card_valid VARCHAR(255) NOT NULL, CHANGE health_card_valid health_card_valid VARCHAR(255) NOT NULL, CHANGE bank_statement_valid bank_statement_valid VARCHAR(255) NOT NULL, CHANGE proof_of_residence_valid proof_of_residence_valid VARCHAR(255) NOT NULL, CHANGE secure_driving_right_certificate_valid secure_driving_right_certificate_valid VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE driver_documents CHANGE vtc_card_valid vtc_card_valid TINYINT(1) NOT NULL, CHANGE driving_license_valid driving_license_valid TINYINT(1) NOT NULL, CHANGE identity_card_valid identity_card_valid TINYINT(1) NOT NULL, CHANGE health_card_valid health_card_valid TINYINT(1) NOT NULL, CHANGE bank_statement_valid bank_statement_valid TINYINT(1) NOT NULL, CHANGE proof_of_residence_valid proof_of_residence_valid TINYINT(1) NOT NULL, CHANGE secure_driving_right_certificate_valid secure_driving_right_certificate_valid TINYINT(1) NOT NULL');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
