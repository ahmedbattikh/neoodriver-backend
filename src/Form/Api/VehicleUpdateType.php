<?php
declare(strict_types=1);

namespace App\Form\Api;

use App\Entity\Vehicle;
use App\Enum\EnergyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class VehicleUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('registrationNumber', TextType::class, ['required' => false])
            ->add('make', TextType::class, ['required' => false])
            ->add('model', TextType::class, ['required' => false])
            ->add('firstRegistrationYear', IntegerType::class, ['required' => false])
            ->add('registrationDate', DateType::class, ['required' => false, 'widget' => 'single_text'])
            ->add('seatCount', IntegerType::class, ['required' => false])
            ->add('energyType', ChoiceType::class, [
                'required' => false,
                'choices' => array_reduce(EnergyType::cases(), function(array $acc, EnergyType $case){ $acc[$case->name] = $case; return $acc; }, []),
                'choice_value' => fn (?EnergyType $et) => $et?->value,
                'choice_label' => fn (EnergyType $et) => $et->name,
            ])
            ->add('insuranceExpirationDate', DateType::class, ['required' => false, 'widget' => 'single_text'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Vehicle::class]);
    }
}

