<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Goals;
use App\Enum\DriverClass;
use App\Enum\GoalFrequency;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
class GoalsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Goals::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Goal')
            ->setEntityLabelInPlural('Goals')
            ->setPageTitle(Crud::PAGE_INDEX, 'Goals')
            ->setPageTitle(Crud::PAGE_NEW, 'Create Goal')
            ->setPageTitle(Crud::PAGE_EDIT, 'Edit Goal')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Goal Details')
            ->setDefaultSort(['updatedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name', 'Name');
        $amount = NumberField::new('amount', 'Amount')->setNumDecimals(3);

        $freqChoices = [
            'Daily' => GoalFrequency::DAILY->value,
            'Weekly' => GoalFrequency::WEEKLY->value,
            'Monthly' => GoalFrequency::MONTHLY->value,
        ];
        $frequency = ChoiceField::new('frequency', 'Frequency')
            ->setChoices($freqChoices)
            ->allowMultipleChoices(false)
            ->renderExpanded(false);

        $targetChoices = [
            'Class 1' => DriverClass::CLASS1->value,
            'Class 2' => DriverClass::CLASS2->value,
            'Class 3' => DriverClass::CLASS3->value,
            'Class 5' => DriverClass::CLASS5->value,
        ];
        $targetClassesEdit = ChoiceField::new('targetClasses', 'Target Classes')
            ->setChoices($targetChoices)
            ->allowMultipleChoices(true)
            ->renderExpanded(false)
            ->setRequired(false);
        $targetClassesView = ArrayField::new('targetClasses', 'Target Classes');

        $enabled = BooleanField::new('enabled', 'Enabled');
        $createdAt = DateTimeField::new('createdAt', 'Created At')->setFormTypeOption('disabled', true);
        $updatedAt = DateTimeField::new('updatedAt', 'Updated At')->setFormTypeOption('disabled', true);

        if ($pageName === Crud::PAGE_INDEX) {
            return [$name, $amount, $frequency, $enabled, $updatedAt];
        }
        if ($pageName === Crud::PAGE_DETAIL) {
            return [$name, $amount, $frequency, $targetClassesView, $enabled, $createdAt, $updatedAt];
        }
        if ($pageName === Crud::PAGE_NEW) {
            return [$name, $amount, $frequency, $targetClassesEdit, $enabled];
        }
        if ($pageName === Crud::PAGE_EDIT) {
            return [$name, $amount, $frequency, $targetClassesEdit, $enabled];
        }
        return [$name, $amount, $frequency, $enabled];
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $alias = $qb->getRootAliases()[0] ?? 'entity';
        $qb->andWhere(sprintf('%s.enabled = :enabled', $alias))
            ->setParameter('enabled', true);
        return $qb;
    }
}
