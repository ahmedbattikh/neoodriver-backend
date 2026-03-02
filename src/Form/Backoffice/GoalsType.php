<?php

declare(strict_types=1);

namespace App\Form\Backoffice;

use App\Entity\Goals;
use App\Enum\DriverClass;
use App\Enum\GoalFrequency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GoalsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('amount', TextType::class)
            ->add('frequency', ChoiceType::class, [
                'choices' => [
                    'Daily' => GoalFrequency::DAILY->value,
                    'Weekly' => GoalFrequency::WEEKLY->value,
                    'Monthly' => GoalFrequency::MONTHLY->value,
                ],
            ])
            ->add('targetClasses', ChoiceType::class, [
                'choices' => [
                    'Class 1' => DriverClass::CLASS1->value,
                    'Class 2' => DriverClass::CLASS2->value,
                    'Class 3' => DriverClass::CLASS3->value,
                    'Class 5' => DriverClass::CLASS5->value,
                ],
                'multiple' => true,
                'required' => false,
            ])
            ->add('enabled', CheckboxType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Goals::class]);
    }
}
