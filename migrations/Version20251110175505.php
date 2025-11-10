<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251110175505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE attachment (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, file_name VARCHAR(255) NOT NULL, original_file_name VARCHAR(255) DEFAULT NULL, file_path VARCHAR(255) NOT NULL, file_size INT NOT NULL, mime_type VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, is_private TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL, description LONGTEXT DEFAULT NULL, uploaded_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', checksum VARCHAR(64) NOT NULL, INDEX IDX_795FD9BBA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE company_documents (id INT AUTO_INCREMENT NOT NULL, driver_id INT NOT NULL, employment_contract_id INT DEFAULT NULL, employer_certificate_id INT DEFAULT NULL, pre_employment_declaration_id INT DEFAULT NULL, mutual_insurance_certificate_id INT DEFAULT NULL, urssaf_compliance_certificate_id INT DEFAULT NULL, kbis_extract_id INT DEFAULT NULL, revtc_registration_certificate_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_91ABF878C3423909 (driver_id), INDEX IDX_91ABF878461F8ACA (employment_contract_id), INDEX IDX_91ABF878DE396166 (employer_certificate_id), INDEX IDX_91ABF8783F29BD44 (pre_employment_declaration_id), INDEX IDX_91ABF878B476F23E (mutual_insurance_certificate_id), INDEX IDX_91ABF87827A3B587 (urssaf_compliance_certificate_id), INDEX IDX_91ABF87858610B34 (kbis_extract_id), INDEX IDX_91ABF878DE29C0BD (revtc_registration_certificate_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE driver (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_11667CD9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE driver_documents (id INT AUTO_INCREMENT NOT NULL, driver_id INT NOT NULL, identity_photo_id INT DEFAULT NULL, vtc_card_front_id INT DEFAULT NULL, vtc_card_back_id INT DEFAULT NULL, driving_license_front_id INT DEFAULT NULL, driving_license_back_id INT DEFAULT NULL, identity_card_front_id INT DEFAULT NULL, identity_card_back_id INT DEFAULT NULL, health_card_id INT DEFAULT NULL, bank_statement_id INT DEFAULT NULL, proof_of_residence_id INT DEFAULT NULL, secure_driving_right_certificate_id INT DEFAULT NULL, vtc_card_valid TINYINT(1) NOT NULL, vtc_card_expiration_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', driving_license_valid TINYINT(1) NOT NULL, driving_license_expiration_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', identity_card_valid TINYINT(1) NOT NULL, identity_card_expiration_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', health_card_valid TINYINT(1) NOT NULL, social_security_number VARCHAR(32) DEFAULT NULL, bank_statement_valid TINYINT(1) NOT NULL, iban VARCHAR(34) DEFAULT NULL, is_hosted TINYINT(1) NOT NULL, proof_of_residence_valid TINYINT(1) NOT NULL, secure_driving_right_certificate_valid TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8D927991C3423909 (driver_id), INDEX IDX_8D9279913D751045 (identity_photo_id), INDEX IDX_8D9279913F0D47B5 (vtc_card_front_id), INDEX IDX_8D927991F7BD1538 (vtc_card_back_id), INDEX IDX_8D927991D332322E (driving_license_front_id), INDEX IDX_8D927991AB3AA8F9 (driving_license_back_id), INDEX IDX_8D9279918A03FA9 (identity_card_front_id), INDEX IDX_8D92799182FEF9D3 (identity_card_back_id), INDEX IDX_8D927991D7650767 (health_card_id), INDEX IDX_8D92799144045140 (bank_statement_id), INDEX IDX_8D9279917BA4841F (proof_of_residence_id), INDEX IDX_8D92799180D14BE1 (secure_driving_right_certificate_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vehicle (id INT AUTO_INCREMENT NOT NULL, driver_id INT NOT NULL, registration_certificate_id INT DEFAULT NULL, paid_transport_insurance_certificate_id INT DEFAULT NULL, technical_inspection_id INT DEFAULT NULL, vehicle_front_photo_id INT DEFAULT NULL, insurance_note_id INT DEFAULT NULL, registration_number VARCHAR(32) NOT NULL, make VARCHAR(64) NOT NULL, model VARCHAR(64) NOT NULL, first_registration_year INT NOT NULL, registration_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', seat_count INT NOT NULL, energy_type VARCHAR(255) NOT NULL, insurance_expiration_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1B80E486C3423909 (driver_id), INDEX IDX_1B80E486BEBCD1AF (registration_certificate_id), INDEX IDX_1B80E486E9C9A2FC (paid_transport_insurance_certificate_id), INDEX IDX_1B80E4869712828 (technical_inspection_id), INDEX IDX_1B80E486157BF883 (vehicle_front_photo_id), INDEX IDX_1B80E486CBCC2DF0 (insurance_note_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE company_documents ADD CONSTRAINT FK_91ABF878C3423909 FOREIGN KEY (driver_id) REFERENCES driver (id)');
        $this->addSql('ALTER TABLE company_documents ADD CONSTRAINT FK_91ABF878461F8ACA FOREIGN KEY (employment_contract_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE company_documents ADD CONSTRAINT FK_91ABF878DE396166 FOREIGN KEY (employer_certificate_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE company_documents ADD CONSTRAINT FK_91ABF8783F29BD44 FOREIGN KEY (pre_employment_declaration_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE company_documents ADD CONSTRAINT FK_91ABF878B476F23E FOREIGN KEY (mutual_insurance_certificate_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE company_documents ADD CONSTRAINT FK_91ABF87827A3B587 FOREIGN KEY (urssaf_compliance_certificate_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE company_documents ADD CONSTRAINT FK_91ABF87858610B34 FOREIGN KEY (kbis_extract_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE company_documents ADD CONSTRAINT FK_91ABF878DE29C0BD FOREIGN KEY (revtc_registration_certificate_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver ADD CONSTRAINT FK_11667CD9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE driver_documents ADD CONSTRAINT FK_8D927991C3423909 FOREIGN KEY (driver_id) REFERENCES driver (id)');
        $this->addSql('ALTER TABLE driver_documents ADD CONSTRAINT FK_8D9279913D751045 FOREIGN KEY (identity_photo_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver_documents ADD CONSTRAINT FK_8D9279913F0D47B5 FOREIGN KEY (vtc_card_front_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver_documents ADD CONSTRAINT FK_8D927991F7BD1538 FOREIGN KEY (vtc_card_back_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver_documents ADD CONSTRAINT FK_8D927991D332322E FOREIGN KEY (driving_license_front_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver_documents ADD CONSTRAINT FK_8D927991AB3AA8F9 FOREIGN KEY (driving_license_back_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver_documents ADD CONSTRAINT FK_8D9279918A03FA9 FOREIGN KEY (identity_card_front_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver_documents ADD CONSTRAINT FK_8D92799182FEF9D3 FOREIGN KEY (identity_card_back_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver_documents ADD CONSTRAINT FK_8D927991D7650767 FOREIGN KEY (health_card_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver_documents ADD CONSTRAINT FK_8D92799144045140 FOREIGN KEY (bank_statement_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver_documents ADD CONSTRAINT FK_8D9279917BA4841F FOREIGN KEY (proof_of_residence_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver_documents ADD CONSTRAINT FK_8D92799180D14BE1 FOREIGN KEY (secure_driving_right_certificate_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486C3423909 FOREIGN KEY (driver_id) REFERENCES driver (id)');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486BEBCD1AF FOREIGN KEY (registration_certificate_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486E9C9A2FC FOREIGN KEY (paid_transport_insurance_certificate_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E4869712828 FOREIGN KEY (technical_inspection_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486157BF883 FOREIGN KEY (vehicle_front_photo_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486CBCC2DF0 FOREIGN KEY (insurance_note_id) REFERENCES attachment (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX IDX_LOGIN_OTPS_EMAIL ON login_otps');
        $this->addSql('DROP INDEX IDX_LOGIN_OTPS_EXPIRES_AT ON login_otps');
        $this->addSql('ALTER TABLE login_otps CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BBA76ED395');
        $this->addSql('ALTER TABLE company_documents DROP FOREIGN KEY FK_91ABF878C3423909');
        $this->addSql('ALTER TABLE company_documents DROP FOREIGN KEY FK_91ABF878461F8ACA');
        $this->addSql('ALTER TABLE company_documents DROP FOREIGN KEY FK_91ABF878DE396166');
        $this->addSql('ALTER TABLE company_documents DROP FOREIGN KEY FK_91ABF8783F29BD44');
        $this->addSql('ALTER TABLE company_documents DROP FOREIGN KEY FK_91ABF878B476F23E');
        $this->addSql('ALTER TABLE company_documents DROP FOREIGN KEY FK_91ABF87827A3B587');
        $this->addSql('ALTER TABLE company_documents DROP FOREIGN KEY FK_91ABF87858610B34');
        $this->addSql('ALTER TABLE company_documents DROP FOREIGN KEY FK_91ABF878DE29C0BD');
        $this->addSql('ALTER TABLE driver DROP FOREIGN KEY FK_11667CD9A76ED395');
        $this->addSql('ALTER TABLE driver_documents DROP FOREIGN KEY FK_8D927991C3423909');
        $this->addSql('ALTER TABLE driver_documents DROP FOREIGN KEY FK_8D9279913D751045');
        $this->addSql('ALTER TABLE driver_documents DROP FOREIGN KEY FK_8D9279913F0D47B5');
        $this->addSql('ALTER TABLE driver_documents DROP FOREIGN KEY FK_8D927991F7BD1538');
        $this->addSql('ALTER TABLE driver_documents DROP FOREIGN KEY FK_8D927991D332322E');
        $this->addSql('ALTER TABLE driver_documents DROP FOREIGN KEY FK_8D927991AB3AA8F9');
        $this->addSql('ALTER TABLE driver_documents DROP FOREIGN KEY FK_8D9279918A03FA9');
        $this->addSql('ALTER TABLE driver_documents DROP FOREIGN KEY FK_8D92799182FEF9D3');
        $this->addSql('ALTER TABLE driver_documents DROP FOREIGN KEY FK_8D927991D7650767');
        $this->addSql('ALTER TABLE driver_documents DROP FOREIGN KEY FK_8D92799144045140');
        $this->addSql('ALTER TABLE driver_documents DROP FOREIGN KEY FK_8D9279917BA4841F');
        $this->addSql('ALTER TABLE driver_documents DROP FOREIGN KEY FK_8D92799180D14BE1');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486C3423909');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486BEBCD1AF');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486E9C9A2FC');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E4869712828');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486157BF883');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486CBCC2DF0');
        $this->addSql('DROP TABLE attachment');
        $this->addSql('DROP TABLE company_documents');
        $this->addSql('DROP TABLE driver');
        $this->addSql('DROP TABLE driver_documents');
        $this->addSql('DROP TABLE vehicle');
        $this->addSql('ALTER TABLE login_otps CHANGE expires_at expires_at DATETIME NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('CREATE INDEX IDX_LOGIN_OTPS_EMAIL ON login_otps (email)');
        $this->addSql('CREATE INDEX IDX_LOGIN_OTPS_EXPIRES_AT ON login_otps (expires_at)');
    }
}
