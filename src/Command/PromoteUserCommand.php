<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:user:promote', description: 'Grant a role to an existing user')] 
final class PromoteUserCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('role', InputArgument::REQUIRED, 'Role to grant, e.g. ROLE_SUPER_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');
        $role = strtoupper((string) $input->getArgument('role'));

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $io->error(sprintf('User "%s" not found.', $email));
            return Command::FAILURE;
        }

        $roles = $user->getRoles();
        if (!\in_array($role, $roles, true)) {
            $roles[] = $role;
            // Persist only custom roles; getRoles() adds ROLE_USER automatically.
            $user->setRoles(array_values(array_filter($roles, fn(string $r) => $r !== 'ROLE_USER')));
            $this->em->flush();
            $io->success(sprintf('Granted %s to %s.', $role, $email));
        } else {
            $io->writeln(sprintf('User %s already has %s.', $email, $role));
        }

        return Command::SUCCESS;
    }
}