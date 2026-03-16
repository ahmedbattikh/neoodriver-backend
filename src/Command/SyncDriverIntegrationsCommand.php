<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\IntegrationSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:integrations:sync', description: 'Sync integration orders for all drivers')]
final class SyncDriverIntegrationsCommand extends Command
{
    public function __construct(
        private readonly IntegrationSyncService $integrationSyncService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'Start date/time in UTC (e.g. 2026-03-01 00:00:00)')
            ->addOption('end', null, InputOption::VALUE_REQUIRED, 'End date/time in UTC (e.g. 2026-03-02 00:00:00)')
            ->addOption('hours', null, InputOption::VALUE_REQUIRED, 'Hours back from now in UTC', '2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tz = new \DateTimeZone('UTC');
        $startRaw = $input->getOption('start');
        $endRaw = $input->getOption('end');
        $hoursRaw = $input->getOption('hours');

        try {
            if ($startRaw || $endRaw) {
                $start = $startRaw ? new \DateTimeImmutable((string) $startRaw, $tz) : new \DateTimeImmutable('today 00:00:00', $tz);
                $end = $endRaw ? new \DateTimeImmutable((string) $endRaw, $tz) : new \DateTimeImmutable('tomorrow 00:00:00', $tz);
                $hours = (int) max(1, (int) floor(($end->getTimestamp() - $start->getTimestamp()) / 3600));
            } else {
                $hours = is_numeric($hoursRaw) ? (int) $hoursRaw : 0;
                if ($hours <= 0) {
                    $io->error('Hours must be a positive number.');
                    return Command::FAILURE;
                }
                $end = new \DateTimeImmutable('now', $tz);
                $start = $end->modify(sprintf('-%d hours', $hours));
            }
        } catch (\Throwable $e) {
            $io->error('Invalid start/end date format.');
            return Command::FAILURE;
        }
        if ($end <= $start) {
            $io->error('End date must be after start date.');
            return Command::FAILURE;
        }
        $log = $this->integrationSyncService->runForWindow($start, $end, $hours, 'command');
        if ($log->getStatus() !== 'SUCCESS') {
            $io->error((string) ($log->getErrorMessage() ?? 'Sync failed'));
            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Synced %d operations across %d accounts.',
            (int) ($log->getTotalOps() ?? 0),
            (int) ($log->getSyncedAccounts() ?? 0)
        ));
        return Command::SUCCESS;
    }
}
