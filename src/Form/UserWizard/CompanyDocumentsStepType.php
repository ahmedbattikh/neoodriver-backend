<?php
declare(strict_types=1);

namespace App\Form\UserWizard;

use App\Entity\CompanyDocuments;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CompanyDocumentsStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('employmentContract', FileType::class, ['mapped' => false, 'required' => false])
            ->add('employerCertificate', FileType::class, ['mapped' => false, 'required' => false])
            ->add('preEmploymentDeclaration', FileType::class, ['mapped' => false, 'required' => false])
            ->add('mutualInsuranceCertificate', FileType::class, ['mapped' => false, 'required' => false])
            ->add('urssafComplianceCertificate', FileType::class, ['mapped' => false, 'required' => false])
            ->add('kbisExtract', FileType::class, ['mapped' => false, 'required' => false])
            ->add('revtcRegistrationCertificate', FileType::class, ['mapped' => false, 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CompanyDocuments::class]);
    }
}