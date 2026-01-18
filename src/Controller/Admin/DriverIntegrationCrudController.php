<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\DriverIntegration;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use EasyCorp\Bundle\EasyAdminBundle\Field;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class DriverIntegrationCrudController extends AbstractCrudController
{
    private function ensureUploadDir(): void
    {
        $dir = (string) $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'integration';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }

    public static function getEntityFqcn(): string
    {
        return DriverIntegration::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Integration')
            ->setEntityLabelInPlural('Integrations');
    }

    public function configureFields(string $pageName): iterable
    {
        $this->ensureUploadDir();
        $code = TextField::new('code');
        $name = TextField::new('name');
        $description = TextareaField::new('description')->setRequired(false);
        $enabled = BooleanField::new('enabled');
        $logo = ImageField::new('logoPath')
            ->setBasePath('uploads/integration')
            ->setUploadDir('public/uploads/integration')
            ->setUploadedFileNamePattern('[timestamp]-[slug].[extension]')
            ->setRequired(false);
        $boltCustomerId = TextField::new('boltCustomerId')->setRequired(false)->setHelp('Bolt OAuth Client ID');
        $boltCustomerSecret = TextField::new('boltCustomerSecret')->setRequired(false)->setHelp('Bolt OAuth Client Secret');
        $boltScope = TextField::new('boltScope')->setRequired(false)->setHelp('Bolt OAuth Scope');
        $boltCompanyIds = CollectionField::new('boltCompanyIds')
            ->setRequired(false)
            ->allowAdd()
            ->allowDelete()
            ->setEntryType(IntegerType::class)
            ->setHelp('Bolt company IDs');
        $createdAt = DateTimeField::new('createdAt')->setFormTypeOption('disabled', true);
        $updatedAt = DateTimeField::new('updatedAt')->setFormTypeOption('disabled', true);

        if ($pageName === Crud::PAGE_INDEX) {
            return [$code, $name, $enabled, $logo];
        }
        if ($pageName === Crud::PAGE_DETAIL) {
            return [$code, $name, $description, $enabled, $logo, $boltCustomerId, $boltScope, $boltCompanyIds, $createdAt, $updatedAt];
        }
        if ($pageName === Crud::PAGE_NEW) {
            return [$code, $name, $description, $enabled, $logo, $boltCustomerId, $boltCustomerSecret, $boltScope, $boltCompanyIds];
        }
        if ($pageName === Crud::PAGE_EDIT) {
            return [$code, $name, $description, $enabled, $logo, $boltCustomerId, $boltCustomerSecret, $boltScope, $boltCompanyIds];
        }
        return [$code, $name, $description, $enabled, $logo, $boltCustomerId, $boltScope, $boltCompanyIds];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->ensureUploadDir();
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->ensureUploadDir();
        parent::updateEntity($entityManager, $entityInstance);
    }
}
