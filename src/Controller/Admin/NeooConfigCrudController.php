<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\NeooConfig;
use App\Form\Backoffice\NeooConfigType;
use App\Service\BackofficeMenuBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class NeooConfigCrudController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly BackofficeMenuBuilder $menuBuilder) {}

    #[Route('/backoffice/neoo-config', name: 'backoffice_neoo_config_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $configs = $this->em->getRepository(NeooConfig::class)->findBy([], ['updatedAt' => 'DESC']);
        return $this->render('backoffice/configuration/neoo_config/index.html.twig', [
            'configs' => $configs,
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/neoo-config/new', name: 'backoffice_neoo_config_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $config = new NeooConfig();
        $form = $this->createForm(NeooConfigType::class, $config);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($config);
            $this->em->flush();
            $this->addFlash('success', 'Config created.');
            return $this->redirectToRoute('backoffice_neoo_config_index');
        }
        return $this->render('backoffice/configuration/neoo_config/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Create Config',
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/neoo-config/{id}/edit', name: 'backoffice_neoo_config_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $config = $this->em->getRepository(NeooConfig::class)->find($id);
        if (!$config instanceof NeooConfig) {
            return $this->redirectToRoute('backoffice_neoo_config_index');
        }
        $form = $this->createForm(NeooConfigType::class, $config);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Config updated.');
            return $this->redirectToRoute('backoffice_neoo_config_index');
        }
        return $this->render('backoffice/configuration/neoo_config/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edit Config',
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/neoo-config/{id}/delete', name: 'backoffice_neoo_config_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, int $id): Response
    {
        $config = $this->em->getRepository(NeooConfig::class)->find($id);
        if (!$config instanceof NeooConfig) {
            return $this->redirectToRoute('backoffice_neoo_config_index');
        }
        $token = (string) $request->request->get('_token', '');
        if ($this->isCsrfTokenValid('delete_neoo_config_' . $config->getId(), $token)) {
            $this->em->remove($config);
            $this->em->flush();
            $this->addFlash('success', 'Config deleted.');
        }
        return $this->redirectToRoute('backoffice_neoo_config_index');
    }
}
