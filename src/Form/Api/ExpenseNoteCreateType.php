<?php

declare(strict_types=1);

namespace App\Form\Api;

use App\Entity\ExpenseNote;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ExpenseNoteCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('noteDate', DateType::class, ['required' => true, 'widget' => 'single_text', 'input' => 'datetime_immutable', 'mapped' => false])
            ->add('amountTtc', TextType::class, ['required' => true])
            ->add('type', TextType::class, ['required' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExpenseNote::class,
            'csrf_protection' => false,
        ]);
    }
}
