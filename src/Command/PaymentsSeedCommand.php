<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\Driver;
use App\Entity\DriverIntegration;
use App\Entity\PaymentBatch;
use App\Entity\PaymentOperation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:payments:seed', description: 'Seed dummy PaymentOperation and PaymentBatch data for a driver and month')]
final class PaymentsSeedCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('driverId', InputArgument::REQUIRED, 'Driver ID')
            ->addArgument('month', InputArgument::REQUIRED, 'Month in format YYYY-MM')
            ->addArgument('integrationCode', InputArgument::OPTIONAL, 'Integration code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $driverId = (int) $input->getArgument('driverId');
        $monthStr = (string) $input->getArgument('month');
        $integrationCode = (string) ($input->getArgument('integrationCode') ?? '');

        $driver = $this->em->getRepository(Driver::class)->find($driverId);
        if (!$driver instanceof Driver) {
            $io->error('Driver not found: ' . $driverId);
            return Command::FAILURE;
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', sprintf('%s-01 00:00:00', $monthStr));
        if (!$start) {
            $io->error('Invalid month format. Use YYYY-MM');
            return Command::FAILURE;
        }
        $end = $start->modify('last day of this month')->setTime(23, 59, 59);

        $integration = null;
        if ($integrationCode !== '') {
            $integration = $this->em->getRepository(DriverIntegration::class)->findOneBy(['code' => $integrationCode]);
        }
        if (!$integration instanceof DriverIntegration) {
            $integration = $this->em->getRepository(DriverIntegration::class)->findOneBy([]) ?: null;
        }
        if (!$integration instanceof DriverIntegration) {
            $integration = new DriverIntegration();
            $integration->setCode('DUMMY');
            $integration->setName('Dummy Integration');
            $integration->setEnabled(true);
            $this->em->persist($integration);
            $this->em->flush();
        }

        $opsCount = 0;
        $credits = 0.0;
        $debits = 0.0;

        $cursor = $start;
        while ($cursor <= $end) {
            $rides = random_int(3, 8);
            for ($i = 0; $i < $rides; $i++) {
                $amount = random_int(800, 4500) / 100.0;
                $op = new PaymentOperation();
                $op->setDriver($driver);
                $op->setIntegrationCode($integration->getCode());
                $op->setOperationType('ride_payment');
                $op->setDirection('credit');
                $op->setAmount(number_format($amount, 3, '.', ''));
                $op->setCurrency('TND');
                $op->setPaymentMethod(random_int(0, 1) === 0 ? 'CASH' : 'CB');
                $op->setBonus(number_format(random_int(0, 300) / 100.0, 3, '.', ''));
                $op->setTips(number_format(random_int(0, 500) / 100.0, 3, '.', ''));
                $op->setStatus('completed');
                $op->setExternalReference('RID-' . strtoupper(bin2hex(random_bytes(4))));
                $op->setDescription(null);
                $occurred = $cursor->setTime(random_int(8, 22), random_int(0, 59), random_int(0, 59));
                $op->setOccurredAt($occurred);
                $this->em->persist($op);
                $opsCount++;
                $credits += $amount;
            }
            $commission = random_int(200, 1200) / 100.0;
            $op2 = new PaymentOperation();
            $op2->setDriver($driver);
            $op2->setIntegrationCode($integration->getCode());
            $op2->setOperationType('commission');
            $op2->setDirection('debit');
            $op2->setAmount(number_format($commission, 3, '.', ''));
            $op2->setCurrency('TND');
            $op2->setPaymentMethod('CB');
            $op2->setBonus('0.000');
            $op2->setTips('0.000');
            $op2->setStatus('completed');
            $op2->setExternalReference('COM-' . strtoupper(bin2hex(random_bytes(4))));
            $op2->setDescription(null);
            $op2->setOccurredAt($cursor->setTime(23, 30, 0));
            $this->em->persist($op2);
            $opsCount++;
            $debits += $commission;

            if (random_int(1, 20) === 10) {
                $refund = random_int(100, 900) / 100.0;
                $op3 = new PaymentOperation();
                $op3->setDriver($driver);
                $op3->setIntegrationCode($integration->getCode());
                $op3->setOperationType('refund');
                $op3->setDirection('credit');
                $op3->setAmount(number_format($refund, 3, '.', ''));
                $op3->setCurrency('TND');
                $op3->setPaymentMethod('CB');
                $op3->setBonus('0.000');
                $op3->setTips('0.000');
                $op3->setStatus('completed');
                $op3->setExternalReference('REF-' . strtoupper(bin2hex(random_bytes(4))));
                $op3->setDescription(null);
                $op3->setOccurredAt($cursor->setTime(12, 0, 0));
                $this->em->persist($op3);
                $opsCount++;
                $credits += $refund;
            }

            $cursor = $cursor->modify('+1 day');
        }

        $weekStart = $start;
        while ($weekStart <= $end) {
            $weekEnd = $weekStart->modify('+6 days');
            if ($weekEnd > $end) {
                $weekEnd = $end;
            }
            $batch = new PaymentBatch();
            $batch->setIntegration($integration);
            $batch->setPeriodStart(\DateTimeImmutable::createFromFormat('Y-m-d', $weekStart->format('Y-m-d')));
            $batch->setPeriodEnd(\DateTimeImmutable::createFromFormat('Y-m-d', $weekEnd->format('Y-m-d')));
            $net = max(0.0, $credits - $debits) / 4.0;
            $batch->setTotalAmount(number_format($net, 3, '.', ''));
            $this->em->persist($batch);

            $payout = new PaymentOperation();
            $payout->setDriver($driver);
            $payout->setIntegrationCode($integration->getCode());
            $payout->setOperationType('payout');
            $payout->setDirection('credit');
            $payout->setAmount(number_format($net, 3, '.', ''));
            $payout->setCurrency('TND');
            $payout->setPaymentMethod('CB');
            $payout->setBonus('0.000');
            $payout->setTips('0.000');
            $payout->setStatus('completed');
            $payout->setExternalReference('PAY-' . strtoupper(bin2hex(random_bytes(4))));
            $payout->setDescription('Weekly payout');
            $payout->setOccurredAt($weekEnd->setTime(18, 0, 0));
            $this->em->persist($payout);
            $opsCount++;

            $weekStart = $weekStart->modify('+7 days');
        }

        $this->em->flush();

        $io->success(sprintf('Seeded %d operations and weekly batches for driver %d in %s.', $opsCount, $driverId, $monthStr));
        return Command::SUCCESS;
    }
}
