<?php

declare(strict_types=1);

namespace App\Form\Backoffice;

use App\Entity\DriverIntegration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DriverIntegrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class)
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, ['required' => false])
            ->add('logoFile', FileType::class, ['mapped' => false, 'required' => false])
            ->add('enabled', CheckboxType::class, ['required' => false])
            ->add('boltCustomerId', TextType::class, ['required' => false])
            ->add('boltCustomerSecret', TextType::class, ['required' => false])
            ->add('boltScope', TextType::class, ['required' => false])
            ->add('boltCompanyIds', TextType::class, ['required' => false]);

        $builder->get('boltCompanyIds')->addModelTransformer(new CallbackTransformer(
            fn (?array $value) => $value === null ? '' : implode(', ', array_map('strval', $value)),
            function ($value): array {
                $raw = array_map('trim', explode(',', (string) $value));
                $filtered = array_filter($raw, fn (string $v) => $v !== '');
                return array_values(array_map('intval', $filtered));
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DriverIntegration::class]);
    }
}
