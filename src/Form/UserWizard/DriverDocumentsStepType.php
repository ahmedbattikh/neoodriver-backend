<?php
declare(strict_types=1);

namespace App\Form\UserWizard;

use App\Entity\DriverDocuments;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DriverDocumentsStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('identityPhoto', FileType::class, ['mapped' => false, 'required' => false])
            ->add('vtcCardFront', FileType::class, ['mapped' => false, 'required' => false])
            ->add('vtcCardBack', FileType::class, ['mapped' => false, 'required' => false])
            ->add('vtcCardValid', CheckboxType::class, ['required' => false])
            ->add('vtcCardExpirationDate', DateType::class, ['required' => false, 'widget' => 'single_text'])
            ->add('drivingLicenseFront', FileType::class, ['mapped' => false, 'required' => false])
            ->add('drivingLicenseBack', FileType::class, ['mapped' => false, 'required' => false])
            ->add('drivingLicenseValid', CheckboxType::class, ['required' => false])
            ->add('drivingLicenseExpirationDate', DateType::class, ['required' => false, 'widget' => 'single_text'])
            ->add('identityCardFront', FileType::class, ['mapped' => false, 'required' => false])
            ->add('identityCardBack', FileType::class, ['mapped' => false, 'required' => false])
            ->add('identityCardValid', CheckboxType::class, ['required' => false])
            ->add('identityCardExpirationDate', DateType::class, ['required' => false, 'widget' => 'single_text'])
            ->add('healthCard', FileType::class, ['mapped' => false, 'required' => false])
            ->add('healthCardValid', CheckboxType::class, ['required' => false])
            ->add('socialSecurityNumber', TextType::class, ['required' => false])
            ->add('bankStatement', FileType::class, ['mapped' => false, 'required' => false])
            ->add('bankStatementValid', CheckboxType::class, ['required' => false])
            ->add('iban', TextType::class, ['required' => false])
            ->add('isHosted', CheckboxType::class, ['required' => false])
            ->add('proofOfResidence', FileType::class, ['mapped' => false, 'required' => false])
            ->add('proofOfResidenceValid', CheckboxType::class, ['required' => false])
            ->add('secureDrivingRightCertificate', FileType::class, ['mapped' => false, 'required' => false])
            ->add('secureDrivingRightCertificateValid', CheckboxType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DriverDocuments::class, 'user' => null]);
    }
}