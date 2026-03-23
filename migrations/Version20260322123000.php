<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize money precision to 2 decimals across application tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE advance_request SET amount = ROUND(amount, 2)');
        $this->addSql('UPDATE conge_request SET amount = ROUND(amount, 2)');
        $this->addSql('UPDATE balance SET sold = ROUND(sold, 2), sold_conge = ROUND(sold_conge, 2), total_debit = ROUND(total_debit, 2)');
        $this->addSql('UPDATE expense_note SET amount_ttc = ROUND(amount_ttc, 2), vat = ROUND(vat, 2) WHERE vat IS NOT NULL');
        $this->addSql('UPDATE goals SET amount = ROUND(amount, 2)');
        $this->addSql('UPDATE neoo_config SET fix_neoo_monthly = ROUND(fix_neoo_monthly, 2), taux_conge = ROUND(taux_conge, 2), frais_km = ROUND(frais_km, 2), taux_pas = ROUND(taux_pas, 2), taux_urssaf = ROUND(taux_urssaf, 2)');
        $this->addSql('UPDATE neoo_fee SET taux = ROUND(taux, 2)');
        $this->addSql('UPDATE payment_batch SET total_amount = ROUND(total_amount, 2)');
        $this->addSql('UPDATE payment_operation SET amount = ROUND(amount, 2), bonus = ROUND(bonus, 2), tips = ROUND(tips, 2), ride_distance = ROUND(ride_distance, 2) WHERE ride_distance IS NOT NULL');

        $this->addSql("ALTER TABLE advance_request CHANGE amount amount NUMERIC(12, 2) NOT NULL");
        $this->addSql("ALTER TABLE conge_request CHANGE amount amount NUMERIC(12, 2) NOT NULL");
        $this->addSql("ALTER TABLE balance CHANGE sold sold NUMERIC(12, 2) NOT NULL, CHANGE sold_conge sold_conge NUMERIC(12, 2) NOT NULL, CHANGE total_debit total_debit NUMERIC(12, 2) NOT NULL");
        $this->addSql("ALTER TABLE expense_note CHANGE amount_ttc amount_ttc NUMERIC(12, 2) NOT NULL, CHANGE vat vat NUMERIC(12, 2) DEFAULT NULL");
        $this->addSql("ALTER TABLE goals CHANGE amount amount NUMERIC(12, 2) NOT NULL");
        $this->addSql("ALTER TABLE neoo_config CHANGE fix_neoo_monthly fix_neoo_monthly NUMERIC(12, 2) NOT NULL, CHANGE taux_conge taux_conge NUMERIC(12, 2) NOT NULL, CHANGE frais_km frais_km NUMERIC(12, 2) NOT NULL, CHANGE taux_pas taux_pas NUMERIC(12, 2) NOT NULL, CHANGE taux_urssaf taux_urssaf NUMERIC(12, 2) NOT NULL");
        $this->addSql("ALTER TABLE neoo_fee CHANGE taux taux NUMERIC(12, 2) NOT NULL");
        $this->addSql("ALTER TABLE payment_batch CHANGE total_amount total_amount NUMERIC(12, 2) NOT NULL");
        $this->addSql("ALTER TABLE payment_operation CHANGE amount amount NUMERIC(12, 2) NOT NULL, CHANGE bonus bonus NUMERIC(12, 2) DEFAULT '0.00' NOT NULL, CHANGE tips tips NUMERIC(12, 2) DEFAULT '0.00' NOT NULL, CHANGE ride_distance ride_distance NUMERIC(12, 2) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE advance_request CHANGE amount amount NUMERIC(12, 3) NOT NULL");
        $this->addSql("ALTER TABLE conge_request CHANGE amount amount NUMERIC(12, 3) NOT NULL");
        $this->addSql("ALTER TABLE balance CHANGE sold sold NUMERIC(12, 3) NOT NULL, CHANGE sold_conge sold_conge NUMERIC(12, 3) NOT NULL, CHANGE total_debit total_debit NUMERIC(12, 3) NOT NULL");
        $this->addSql("ALTER TABLE expense_note CHANGE amount_ttc amount_ttc NUMERIC(12, 3) NOT NULL, CHANGE vat vat NUMERIC(12, 3) DEFAULT NULL");
        $this->addSql("ALTER TABLE goals CHANGE amount amount NUMERIC(12, 3) NOT NULL");
        $this->addSql("ALTER TABLE neoo_config CHANGE fix_neoo_monthly fix_neoo_monthly NUMERIC(12, 3) NOT NULL, CHANGE taux_conge taux_conge NUMERIC(12, 3) NOT NULL, CHANGE frais_km frais_km NUMERIC(12, 3) NOT NULL, CHANGE taux_pas taux_pas NUMERIC(12, 3) NOT NULL, CHANGE taux_urssaf taux_urssaf NUMERIC(12, 3) NOT NULL");
        $this->addSql("ALTER TABLE neoo_fee CHANGE taux taux NUMERIC(12, 3) NOT NULL");
        $this->addSql("ALTER TABLE payment_batch CHANGE total_amount total_amount NUMERIC(12, 3) NOT NULL");
        $this->addSql("ALTER TABLE payment_operation CHANGE amount amount NUMERIC(12, 3) NOT NULL, CHANGE bonus bonus NUMERIC(12, 3) DEFAULT '0.000' NOT NULL, CHANGE tips tips NUMERIC(12, 3) DEFAULT '0.000' NOT NULL, CHANGE ride_distance ride_distance NUMERIC(12, 3) DEFAULT NULL");
    }
}
