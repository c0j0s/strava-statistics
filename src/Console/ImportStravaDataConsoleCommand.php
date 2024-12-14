<?php

namespace App\Console;

use App\Domain\Strava\Activity\ImportActivities\ImportActivities;
use App\Domain\Strava\Activity\Stream\CalculateBestStreamAverages\CalculateBestStreamAverages;
use App\Domain\Strava\Activity\Stream\ImportActivityStreams\ImportActivityStreams;
use App\Domain\Strava\Challenge\ImportChallenges\ImportChallenges;
use App\Domain\Strava\Gear\ImportGear\ImportGear;
use App\Domain\Strava\MaxStravaUsageHasBeenReached;
use App\Domain\Strava\Segment\ImportSegments\ImportSegments;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\Doctrine\MigrationRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:import-data', description: 'Import Strava data')]
final class ImportStravaDataConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly MaxStravaUsageHasBeenReached $maxStravaUsageHasBeenReached,
        private readonly MigrationRunner $migrationRunner,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->maxStravaUsageHasBeenReached->clear();
        $this->migrationRunner->run();

        $this->commandBus->dispatch(new ImportActivities($output));
        $this->commandBus->dispatch(new ImportActivityStreams($output));
        $this->commandBus->dispatch(new ImportSegments($output));
        $this->commandBus->dispatch(new ImportGear($output));
        $this->commandBus->dispatch(new ImportChallenges($output));
        $this->commandBus->dispatch(new CalculateBestStreamAverages($output));

        return Command::SUCCESS;
    }
}
