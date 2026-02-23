<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223220158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_logs (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, level VARCHAR(20) NOT NULL, message LONGTEXT NOT NULL, context JSON DEFAULT NULL, method VARCHAR(10) NOT NULL, path VARCHAR(255) NOT NULL, status_code INT NOT NULL, ip_address VARCHAR(64) DEFAULT NULL, duration_ms INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_app_logs_created_at (created_at), INDEX idx_app_logs_user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE app_logs ADD CONSTRAINT FK_F5A1E3FCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE neoo_config CHANGE fix_neoo_monthly fix_neoo_monthly NUMERIC(12, 3) NOT NULL, CHANGE taux_conge taux_conge NUMERIC(12, 3) NOT NULL, CHANGE frais_km frais_km NUMERIC(12, 3) NOT NULL, CHANGE taux_pas taux_pas NUMERIC(12, 3) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE taux_urssaf taux_urssaf NUMERIC(12, 3) NOT NULL');
        $this->addSql('ALTER TABLE neoo_fee CHANGE taux taux NUMERIC(12, 3) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE payment_batch RENAME INDEX idx_payment_batch_integration_id TO IDX_AF4264669E82DDEA');
        $this->addSql('ALTER TABLE payment_operation CHANGE currency currency VARCHAR(3) NOT NULL, CHANGE payment_method payment_method VARCHAR(255) NOT NULL, CHANGE bonus bonus NUMERIC(12, 3) NOT NULL, CHANGE tips tips NUMERIC(12, 3) NOT NULL');
        $this->addSql('ALTER TABLE payment_operation RENAME INDEX idx_payment_operation_driver_id TO IDX_5AA88945C3423909');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_logs DROP FOREIGN KEY FK_F5A1E3FCA76ED395');
        $this->addSql('DROP TABLE app_logs');
        $this->addSql('ALTER TABLE neoo_config CHANGE fix_neoo_monthly fix_neoo_monthly NUMERIC(12, 3) DEFAULT \'0.000\' NOT NULL, CHANGE taux_conge taux_conge NUMERIC(12, 3) DEFAULT \'0.000\' NOT NULL, CHANGE frais_km frais_km NUMERIC(12, 3) DEFAULT \'0.000\' NOT NULL, CHANGE taux_pas taux_pas NUMERIC(12, 3) DEFAULT \'0.000\' NOT NULL, CHANGE taux_urssaf taux_urssaf NUMERIC(12, 3) DEFAULT \'0.000\' NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE neoo_fee CHANGE taux taux NUMERIC(12, 3) DEFAULT \'0.000\' NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE payment_batch RENAME INDEX idx_af4264669e82ddea TO IDX_PAYMENT_BATCH_INTEGRATION_ID');
        $this->addSql('ALTER TABLE payment_operation CHANGE payment_method payment_method VARCHAR(8) DEFAULT \'CB\' NOT NULL, CHANGE bonus bonus NUMERIC(12, 3) DEFAULT \'0.000\' NOT NULL, CHANGE tips tips NUMERIC(12, 3) DEFAULT \'0.000\' NOT NULL, CHANGE currency currency VARCHAR(3) DEFAULT \'TND\' NOT NULL');
        $this->addSql('ALTER TABLE payment_operation RENAME INDEX idx_5aa88945c3423909 TO IDX_PAYMENT_OPERATION_DRIVER_ID');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
