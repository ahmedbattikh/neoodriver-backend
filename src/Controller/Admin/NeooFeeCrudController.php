<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\NeooFee;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
class NeooFeeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NeooFee::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Neoo Fee')
            ->setEntityLabelInPlural('Neoo Fees')
            ->setPageTitle(Crud::PAGE_INDEX, 'Neoo Fees')
            ->setPageTitle(Crud::PAGE_NEW, 'Create Fee')
            ->setPageTitle(Crud::PAGE_EDIT, 'Edit Fee')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Fee Details')
            ->setDefaultSort(['updatedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id')->onlyOnDetail();
        $taux = NumberField::new('taux', 'Taux')->setNumDecimals(3);
        $start = NumberField::new('start', 'Start')->setNumDecimals(3);
        $end = NumberField::new('end', 'End')->setNumDecimals(3);
        $createdAt = DateTimeField::new('createdAt', 'Created At')->setFormTypeOption('disabled', true)->onlyOnDetail();
        $updatedAt = DateTimeField::new('updatedAt', 'Updated At')->setFormTypeOption('disabled', true)->onlyOnDetail();

        if ($pageName === Crud::PAGE_INDEX) {
            return [$taux, $start, $end];
        }
        if ($pageName === Crud::PAGE_NEW) {
            return [$taux, $start, $end];
        }
        if ($pageName === Crud::PAGE_EDIT) {
            return [$taux, $start, $end];
        }
        return [$id, $taux, $start, $end, $createdAt, $updatedAt];
    }
}
