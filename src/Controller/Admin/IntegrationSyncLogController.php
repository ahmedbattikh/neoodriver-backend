<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\IntegrationSyncLog;
use App\Service\BackofficeMenuBuilder;
use App\Service\IntegrationSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class IntegrationSyncLogController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BackofficeMenuBuilder $menuBuilder,
        private readonly IntegrationSyncService $integrationSyncService,
    ) {}

    #[Route('/backoffice/integration-sync-logs', name: 'backoffice_integration_sync_logs_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $logs = $this->em->getRepository(IntegrationSyncLog::class)->findBy([], ['id' => 'DESC'], 200);

        return $this->render('backoffice/integration_sync_logs/index.html.twig', [
            'logs' => $logs,
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
        ]);
    }

    #[Route('/backoffice/integration-sync-logs/{id}/retry', name: 'backoffice_integration_sync_logs_retry', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function retry(Request $request, int $id): RedirectResponse
    {
        $log = $this->em->getRepository(IntegrationSyncLog::class)->find($id);
        if (!$log instanceof IntegrationSyncLog) {
            $this->addFlash('danger', 'Sync log not found.');
            return $this->redirectToRoute('backoffice_integration_sync_logs_index');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('retry_integration_sync_' . $log->getId(), $token)) {
            $this->addFlash('danger', 'Invalid token.');
            return $this->redirectToRoute('backoffice_integration_sync_logs_index');
        }

        if ($log->getStatus() !== 'FAILED') {
            $this->addFlash('warning', 'Retry is available only for failed runs.');
            return $this->redirectToRoute('backoffice_integration_sync_logs_index');
        }

        $retryLog = $this->integrationSyncService->runForWindow(
            $log->getStartAt(),
            $log->getEndAt(),
            max(1, $log->getHours()),
            'manual_retry',
            $log->getId()
        );

        if ($retryLog->getStatus() === 'SUCCESS') {
            $this->addFlash('success', sprintf(
                'Retry succeeded: %d operations synced across %d accounts.',
                (int) ($retryLog->getTotalOps() ?? 0),
                (int) ($retryLog->getSyncedAccounts() ?? 0)
            ));
        } else {
            $this->addFlash('danger', (string) ($retryLog->getErrorMessage() ?? 'Retry failed.'));
        }

        return $this->redirectToRoute('backoffice_integration_sync_logs_index');
    }
}
