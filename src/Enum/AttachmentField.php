<?php
declare(strict_types=1);

namespace App\Enum;

enum AttachmentField: string
{
    case COMPANY_EMPLOYMENT_CONTRACT = 'COMPANY_EMPLOYMENT_CONTRACT';
    case COMPANY_EMPLOYER_CERTIFICATE = 'COMPANY_EMPLOYER_CERTIFICATE';
    case COMPANY_PRE_EMPLOYMENT_DECLARATION = 'COMPANY_PRE_EMPLOYMENT_DECLARATION';
    case COMPANY_MUTUAL_INSURANCE_CERTIFICATE = 'COMPANY_MUTUAL_INSURANCE_CERTIFICATE';
    case COMPANY_URSSAF_COMPLIANCE_CERTIFICATE = 'COMPANY_URSSAF_COMPLIANCE_CERTIFICATE';
    case COMPANY_KBIS_EXTRACT = 'COMPANY_KBIS_EXTRACT';
    case COMPANY_REVTC_REGISTRATION_CERTIFICATE = 'COMPANY_REVTC_REGISTRATION_CERTIFICATE';

    case DRIVER_IDENTITY_PHOTO = 'DRIVER_IDENTITY_PHOTO';
    case DRIVER_VTC_CARD_FRONT = 'DRIVER_VTC_CARD_FRONT';
    case DRIVER_VTC_CARD_BACK = 'DRIVER_VTC_CARD_BACK';
    case DRIVER_DRIVING_LICENSE_FRONT = 'DRIVER_DRIVING_LICENSE_FRONT';
    case DRIVER_DRIVING_LICENSE_BACK = 'DRIVER_DRIVING_LICENSE_BACK';
    case DRIVER_IDENTITY_CARD_FRONT = 'DRIVER_IDENTITY_CARD_FRONT';
    case DRIVER_IDENTITY_CARD_BACK = 'DRIVER_IDENTITY_CARD_BACK';
    case DRIVER_HEALTH_CARD = 'DRIVER_HEALTH_CARD';
    case DRIVER_BANK_STATEMENT = 'DRIVER_BANK_STATEMENT';
    case DRIVER_PROOF_OF_RESIDENCE = 'DRIVER_PROOF_OF_RESIDENCE';
    case DRIVER_SECURE_DRIVING_RIGHT_CERTIFICATE = 'DRIVER_SECURE_DRIVING_RIGHT_CERTIFICATE';

    case VEHICLE_REGISTRATION_CERTIFICATE = 'VEHICLE_REGISTRATION_CERTIFICATE';
    case VEHICLE_PAID_TRANSPORT_INSURANCE_CERTIFICATE = 'VEHICLE_PAID_TRANSPORT_INSURANCE_CERTIFICATE';
    case VEHICLE_TECHNICAL_INSPECTION = 'VEHICLE_TECHNICAL_INSPECTION';
    case VEHICLE_VEHICLE_FRONT_PHOTO = 'VEHICLE_VEHICLE_FRONT_PHOTO';
    case VEHICLE_INSURANCE_NOTE = 'VEHICLE_INSURANCE_NOTE';
    case USER_PIC_PROFILE = 'USER_PIC_PROFILE';
    case EXPENSE_INVOICE = 'EXPENSE_INVOICE';

    public function folder(): string
    {
        return match ($this) {
            self::COMPANY_EMPLOYMENT_CONTRACT,
            self::COMPANY_EMPLOYER_CERTIFICATE,
            self::COMPANY_PRE_EMPLOYMENT_DECLARATION,
            self::COMPANY_MUTUAL_INSURANCE_CERTIFICATE,
            self::COMPANY_URSSAF_COMPLIANCE_CERTIFICATE,
            self::COMPANY_KBIS_EXTRACT,
            self::COMPANY_REVTC_REGISTRATION_CERTIFICATE => 'company',

            self::DRIVER_IDENTITY_PHOTO,
            self::DRIVER_VTC_CARD_FRONT,
            self::DRIVER_VTC_CARD_BACK,
            self::DRIVER_DRIVING_LICENSE_FRONT,
            self::DRIVER_DRIVING_LICENSE_BACK,
            self::DRIVER_IDENTITY_CARD_FRONT,
            self::DRIVER_IDENTITY_CARD_BACK,
            self::DRIVER_HEALTH_CARD,
            self::DRIVER_BANK_STATEMENT,
            self::DRIVER_PROOF_OF_RESIDENCE,
            self::DRIVER_SECURE_DRIVING_RIGHT_CERTIFICATE => 'driver',

            self::VEHICLE_REGISTRATION_CERTIFICATE,
            self::VEHICLE_PAID_TRANSPORT_INSURANCE_CERTIFICATE,
            self::VEHICLE_TECHNICAL_INSPECTION,
            self::VEHICLE_VEHICLE_FRONT_PHOTO,
            self::VEHICLE_INSURANCE_NOTE => 'vehicle',
            self::USER_PIC_PROFILE => 'user',
            self::EXPENSE_INVOICE => 'expense',
        };
    }

    public function base(): string
    {
        return match ($this) {
            self::COMPANY_EMPLOYMENT_CONTRACT => 'employment_contract',
            self::COMPANY_EMPLOYER_CERTIFICATE => 'employer_certificate',
            self::COMPANY_PRE_EMPLOYMENT_DECLARATION => 'pre_employment_declaration',
            self::COMPANY_MUTUAL_INSURANCE_CERTIFICATE => 'mutual_insurance_certificate',
            self::COMPANY_URSSAF_COMPLIANCE_CERTIFICATE => 'urssaf_compliance_certificate',
            self::COMPANY_KBIS_EXTRACT => 'kbis_extract',
            self::COMPANY_REVTC_REGISTRATION_CERTIFICATE => 'revtc_registration_certificate',

            self::DRIVER_IDENTITY_PHOTO => 'identity_photo',
            self::DRIVER_VTC_CARD_FRONT => 'vtc_card_front',
            self::DRIVER_VTC_CARD_BACK => 'vtc_card_back',
            self::DRIVER_DRIVING_LICENSE_FRONT => 'driving_license_front',
            self::DRIVER_DRIVING_LICENSE_BACK => 'driving_license_back',
            self::DRIVER_IDENTITY_CARD_FRONT => 'identity_card_front',
            self::DRIVER_IDENTITY_CARD_BACK => 'identity_card_back',
            self::DRIVER_HEALTH_CARD => 'health_card',
            self::DRIVER_BANK_STATEMENT => 'bank_statement',
            self::DRIVER_PROOF_OF_RESIDENCE => 'proof_of_residence',
            self::DRIVER_SECURE_DRIVING_RIGHT_CERTIFICATE => 'secure_driving_right_certificate',

            self::VEHICLE_REGISTRATION_CERTIFICATE => 'registration_certificate',
            self::VEHICLE_PAID_TRANSPORT_INSURANCE_CERTIFICATE => 'paid_transport_insurance_certificate',
            self::VEHICLE_TECHNICAL_INSPECTION => 'technical_inspection',
            self::VEHICLE_VEHICLE_FRONT_PHOTO => 'vehicle_front_photo',
            self::VEHICLE_INSURANCE_NOTE => 'insurance_note',
            self::USER_PIC_PROFILE => 'pic_profile',
            self::EXPENSE_INVOICE => 'expense_invoice',
        };
    }

    public function key(string $reference, int $recordId): string
    {
        return $reference . '/' . $this->folder() . '/' . $this->base() . '_' . $recordId;
    }
}
