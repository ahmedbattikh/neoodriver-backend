<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Enum\AttachmentField;
use App\Service\Storage\R2Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:r2:debug', description: 'Ensure user folders in R2 and upload a test object')]
final class R2DebugCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly R2Client $r2,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $io->error(sprintf('User "%s" not found.', $email));
            return Command::FAILURE;
        }

        $reference = $user->getReference();
        if ($reference === null || $reference === '') {
            $reference = 'AU-' . strtoupper(bin2hex(random_bytes(4)));
            $user->setReference($reference);
            $this->em->flush();
            $io->writeln(sprintf('Generated reference: %s', $reference));
        }

        try {
            $this->r2->ensureUserFolders($reference);
            $this->r2->putObject($reference . '/__debug__.txt', 'r2-ok', 'text/plain', [], true);
            $debugKey = AttachmentField::DRIVER_IDENTITY_PHOTO->key($reference, (int) $user->getId());
            $this->r2->putObject($debugKey . '.txt', 'field-ok', 'text/plain', [], true);
            $io->success(sprintf('R2 folders ensured and debug object uploaded for %s.', $reference));
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('R2 operation failed: ' . $e->getMessage());
            $io->error('R2 operation failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}