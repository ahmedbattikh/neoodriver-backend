<?php
declare(strict_types=1);

namespace App\Command;

use App\Enum\VehicleMake;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'app:vehicle-models:export', description: 'Export vehicle makes and models to public/data/all-vehicles-model.json')]
final class ExportVehicleModelsCommand extends Command
{
    public function __construct(private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $map = [];
        foreach (VehicleMake::cases() as $make) {
            $map[$make->label()] = $make->models();
        }
        $json = json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $projectDir = $this->kernel->getProjectDir();
        $dir = $projectDir . '/public/data';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $path = $dir . '/all-vehicles-model.json';
        file_put_contents($path, (string) $json);
        $io->success('Exported ' . count($map) . ' makes to ' . $path);
        return Command::SUCCESS;
    }
}