<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\DriverIntegration;
use App\Form\Backoffice\DriverIntegrationType;
use App\Service\BackofficeMenuBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class DriverIntegrationCrudController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly BackofficeMenuBuilder $menuBuilder) {}

    #[Route('/backoffice/integrations', name: 'backoffice_integrations_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $integrations = $this->em->getRepository(DriverIntegration::class)->findBy([], ['updatedAt' => 'DESC']);
        return $this->render('backoffice/configuration/driver_integrations/index.html.twig', [
            'integrations' => $integrations,
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/integrations/new', name: 'backoffice_integrations_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $integration = new DriverIntegration();
        $form = $this->createForm(DriverIntegrationType::class, $integration);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleLogoUpload($form->get('logoFile')->getData(), $integration);
            $this->em->persist($integration);
            $this->em->flush();
            $this->addFlash('success', 'Integration created.');
            return $this->redirectToRoute('backoffice_integrations_index');
        }
        return $this->render('backoffice/configuration/driver_integrations/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Create Integration',
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/integrations/{id}/edit', name: 'backoffice_integrations_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $integration = $this->em->getRepository(DriverIntegration::class)->find($id);
        if (!$integration instanceof DriverIntegration) {
            return $this->redirectToRoute('backoffice_integrations_index');
        }
        $form = $this->createForm(DriverIntegrationType::class, $integration);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleLogoUpload($form->get('logoFile')->getData(), $integration);
            $this->em->flush();
            $this->addFlash('success', 'Integration updated.');
            return $this->redirectToRoute('backoffice_integrations_index');
        }
        return $this->render('backoffice/configuration/driver_integrations/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edit Integration',
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/integrations/{id}/delete', name: 'backoffice_integrations_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, int $id): Response
    {
        $integration = $this->em->getRepository(DriverIntegration::class)->find($id);
        if (!$integration instanceof DriverIntegration) {
            return $this->redirectToRoute('backoffice_integrations_index');
        }
        $token = (string) $request->request->get('_token', '');
        if ($this->isCsrfTokenValid('delete_integration_' . $integration->getId(), $token)) {
            $this->em->remove($integration);
            $this->em->flush();
            $this->addFlash('success', 'Integration deleted.');
        }
        return $this->redirectToRoute('backoffice_integrations_index');
    }

    private function handleLogoUpload(?UploadedFile $file, DriverIntegration $integration): void
    {
        if (!$file instanceof UploadedFile) {
            return;
        }
        $dir = $this->ensureUploadDir();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $originalName) ?: 'logo';
        $fileName = time() . '-' . $safeName . '.' . $file->guessExtension();
        $file->move($dir, $fileName);
        $integration->setLogoPath($fileName);
    }

    private function ensureUploadDir(): string
    {
        $dir = (string) $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'integration';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }
}
