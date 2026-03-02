<?php

declare(strict_types=1);

namespace App\Form\Backoffice;

use App\Entity\NeooConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class NeooConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fixNeooMonthly', NumberType::class, ['input' => 'string', 'scale' => 3])
            ->add('tauxConge', NumberType::class, ['input' => 'string', 'scale' => 3])
            ->add('fraisKm', NumberType::class, ['input' => 'string', 'scale' => 3])
            ->add('tauxPas', NumberType::class, ['input' => 'string', 'scale' => 3])
            ->add('tauxUrssaf', NumberType::class, ['input' => 'string', 'scale' => 3]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => NeooConfig::class]);
    }
}
