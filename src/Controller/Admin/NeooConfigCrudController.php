<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\NeooConfig;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
class NeooConfigCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NeooConfig::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Neoo Config')
            ->setEntityLabelInPlural('Neoo Config')
            ->setPageTitle(Crud::PAGE_INDEX, 'Neoo Config')
            ->setPageTitle(Crud::PAGE_NEW, 'Create Config')
            ->setPageTitle(Crud::PAGE_EDIT, 'Edit Config')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Config Details')
            ->setDefaultSort(['updatedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id')->onlyOnDetail();
        $fix = MoneyField::new('fixNeooMonthly', 'Fix Neoo Monthly')->setCurrency('TND')->setStoredAsCents(false)->setNumDecimals(3);
        $tConge = NumberField::new('tauxConge', 'Taux Congé')->setNumDecimals(3);
        $fKm = NumberField::new('fraisKm', 'Frais Km')->setNumDecimals(3);
        $tPas = NumberField::new('tauxPas', 'Taux PAS')->setNumDecimals(3);
        $tUrssaf = NumberField::new('tauxUrssaf', 'Taux URSSAF')->setNumDecimals(3);
        $createdAt = DateTimeField::new('createdAt', 'Created At')->setFormTypeOption('disabled', true)->onlyOnDetail();
        $updatedAt = DateTimeField::new('updatedAt', 'Updated At')->setFormTypeOption('disabled', true)->onlyOnDetail();

        if ($pageName === Crud::PAGE_INDEX) {
            return [$fix, $tConge, $fKm, $tPas, $tUrssaf];
        }
        if ($pageName === Crud::PAGE_NEW) {
            return [$fix, $tConge, $fKm, $tPas, $tUrssaf];
        }
        if ($pageName === Crud::PAGE_EDIT) {
            return [$fix, $tConge, $fKm, $tPas, $tUrssaf];
        }
        return [$id, $fix, $tConge, $fKm, $tPas, $tUrssaf, $createdAt, $updatedAt];
    }
}
