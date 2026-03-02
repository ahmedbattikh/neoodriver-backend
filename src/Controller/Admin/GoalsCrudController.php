<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Goals;
use App\Form\Backoffice\GoalsType;
use App\Service\BackofficeMenuBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class GoalsCrudController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly BackofficeMenuBuilder $menuBuilder) {}

    #[Route('/backoffice/goals', name: 'backoffice_goals_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $goals = $this->em->getRepository(Goals::class)->findBy([], ['updatedAt' => 'DESC']);
        return $this->render('backoffice/configuration/goals/index.html.twig', [
            'goals' => $goals,
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/goals/new', name: 'backoffice_goals_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $goal = new Goals();
        $form = $this->createForm(GoalsType::class, $goal);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($goal);
            $this->em->flush();
            $this->addFlash('success', 'Goal created.');
            return $this->redirectToRoute('backoffice_goals_index');
        }
        return $this->render('backoffice/configuration/goals/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Create Goal',
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/goals/{id}/edit', name: 'backoffice_goals_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $goal = $this->em->getRepository(Goals::class)->find($id);
        if (!$goal instanceof Goals) {
            return $this->redirectToRoute('backoffice_goals_index');
        }
        $form = $this->createForm(GoalsType::class, $goal);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Goal updated.');
            return $this->redirectToRoute('backoffice_goals_index');
        }
        return $this->render('backoffice/configuration/goals/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edit Goal',
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/goals/{id}/delete', name: 'backoffice_goals_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, int $id): Response
    {
        $goal = $this->em->getRepository(Goals::class)->find($id);
        if (!$goal instanceof Goals) {
            return $this->redirectToRoute('backoffice_goals_index');
        }
        $token = (string) $request->request->get('_token', '');
        if ($this->isCsrfTokenValid('delete_goal_' . $goal->getId(), $token)) {
            $this->em->remove($goal);
            $this->em->flush();
            $this->addFlash('success', 'Goal deleted.');
        }
        return $this->redirectToRoute('backoffice_goals_index');
    }
}
