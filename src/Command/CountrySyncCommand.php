<?php
declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\CountrySyncService;
use Symfony\Component\Console\Style\SymfonyStyle;

class CountrySyncCommand extends Command
{
    public function __construct(
        private readonly CountrySyncService $syncService,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('countries:sync');
        $this->setDescription('Synchronize the countries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Country Sync');
        $io->text('Fetching data from REST Countries API...');

        try {
            $result = $this->syncService->sync();

            $io->success(sprintf(
                'Sync completed — Created: %d | Updated: %d | Deleted: %d',
                $result['created'],
                $result['updated'],
                $result['deleted'],
            ));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error(sprintf('Sync failed: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}