<?php
declare(strict_types=1);

namespace App\Form\UserWizard;

use App\Entity\Vehicle;
use App\Enum\EnergyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class VehicleStepType extends AbstractType
{
    public function __construct(private readonly string $vehicleModelsPath = '') {}

    private function loadModels(): array
    {
        $path = $this->vehicleModelsPath;
        if ($path === '' || !is_file($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        if (array_is_list($data)) {
            $map = [];
            foreach ($data as $row) {
                if (is_array($row)) {
                    $mk = (string)($row['make'] ?? '');
                    $md = (string)($row['model'] ?? '');
                    if ($mk !== '' && $md !== '') {
                        $map[$mk] = $map[$mk] ?? [];
                        if (!in_array($md, $map[$mk], true)) {
                            $map[$mk][] = $md;
                        }
                    }
                }
            }
            return $map;
        }
        $result = [];
        foreach ($data as $mk => $models) {
            if (is_array($models)) {
                $result[(string)$mk] = array_values(array_unique(array_map(fn($m) => (string)$m, $models)));
            }
        }
        return $result;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $modelsMap = $this->loadModels();
        $makes = array_keys($modelsMap);
        sort($makes, SORT_STRING | SORT_FLAG_CASE);

        $builder->add('registrationNumber', TextType::class);
        $builder
            ->add('make', ChoiceType::class, [
                'choices' => array_combine($makes, $makes),
                'placeholder' => 'Select make',
                'required' => false,
                'attr' => ['data-ea-widget' => 'ea-autocomplete'],
            ])
            ->add('model', ChoiceType::class, [
                'choices' => [],
                'placeholder' => 'Select model',
                'required' => false,
                'attr' => ['data-ea-widget' => 'ea-autocomplete'],
            ]);
        $builder
            ->add('firstRegistrationYear', IntegerType::class)
            ->add('registrationDate', DateType::class, ['required' => false, 'widget' => 'single_text'])
            ->add('seatCount', IntegerType::class)
            ->add('energyType', ChoiceType::class, [
                'choices' => array_reduce(EnergyType::cases(), function(array $acc, EnergyType $case){ $acc[$case->name] = $case; return $acc; }, []),
                'choice_value' => fn (?EnergyType $et) => $et?->value,
                'choice_label' => fn (EnergyType $et) => $et->name,
            ])
            ->add('registrationCertificate', FileType::class, ['mapped' => false, 'required' => false])
            ->add('paidTransportInsuranceCertificate', FileType::class, ['mapped' => false, 'required' => false])
            ->add('technicalInspection', FileType::class, ['mapped' => false, 'required' => false])
            ->add('vehicleFrontPhoto', FileType::class, ['mapped' => false, 'required' => false])
            ->add('insuranceNote', FileType::class, ['mapped' => false, 'required' => false])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($modelsMap) {
            $form = $event->getForm();
            $selectedMake = '';
            $models = $selectedMake !== '' && isset($modelsMap[$selectedMake]) ? $modelsMap[$selectedMake] : [];
            sort($models, SORT_STRING | SORT_FLAG_CASE);
            if ($modelsMap !== []) {
                $form->add('model', ChoiceType::class, [
                    'choices' => array_combine($models, $models),
                    'placeholder' => 'Select model',
                    'required' => false,
                    'attr' => ['data-ea-widget' => 'ea-autocomplete'],
                ]);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($modelsMap) {
            $data = (array)$event->getData();
            $form = $event->getForm();
            $selectedMake = (string)($data['make'] ?? '');
            $models = $selectedMake !== '' && isset($modelsMap[$selectedMake]) ? $modelsMap[$selectedMake] : [];
            sort($models, SORT_STRING | SORT_FLAG_CASE);
            if ($modelsMap !== []) {
                $form->add('model', ChoiceType::class, [
                    'choices' => array_combine($models, $models),
                    'placeholder' => 'Select model',
                    'required' => false,
                    'attr' => ['data-ea-widget' => 'ea-autocomplete'],
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Vehicle::class]);
    }
}