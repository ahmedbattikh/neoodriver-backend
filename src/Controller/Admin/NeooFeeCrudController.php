<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\NeooFee;
use App\Form\Backoffice\NeooFeeType;
use App\Service\BackofficeMenuBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class NeooFeeCrudController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly BackofficeMenuBuilder $menuBuilder) {}

    #[Route('/backoffice/neoo-fees', name: 'backoffice_neoo_fees_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $fees = $this->em->getRepository(NeooFee::class)->findBy([], ['updatedAt' => 'DESC']);
        return $this->render('backoffice/configuration/neoo_fees/index.html.twig', [
            'fees' => $fees,
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/neoo-fees/new', name: 'backoffice_neoo_fees_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $fee = new NeooFee();
        $form = $this->createForm(NeooFeeType::class, $fee);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($fee);
            $this->em->flush();
            $this->addFlash('success', 'Fee created.');
            return $this->redirectToRoute('backoffice_neoo_fees_index');
        }
        return $this->render('backoffice/configuration/neoo_fees/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Create Fee',
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/neoo-fees/{id}/edit', name: 'backoffice_neoo_fees_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $fee = $this->em->getRepository(NeooFee::class)->find($id);
        if (!$fee instanceof NeooFee) {
            return $this->redirectToRoute('backoffice_neoo_fees_index');
        }
        $form = $this->createForm(NeooFeeType::class, $fee);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Fee updated.');
            return $this->redirectToRoute('backoffice_neoo_fees_index');
        }
        return $this->render('backoffice/configuration/neoo_fees/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edit Fee',
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/neoo-fees/{id}/delete', name: 'backoffice_neoo_fees_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, int $id): Response
    {
        $fee = $this->em->getRepository(NeooFee::class)->find($id);
        if (!$fee instanceof NeooFee) {
            return $this->redirectToRoute('backoffice_neoo_fees_index');
        }
        $token = (string) $request->request->get('_token', '');
        if ($this->isCsrfTokenValid('delete_neoo_fee_' . $fee->getId(), $token)) {
            $this->em->remove($fee);
            $this->em->flush();
            $this->addFlash('success', 'Fee deleted.');
        }
        return $this->redirectToRoute('backoffice_neoo_fees_index');
    }
}
