<?php

declare(strict_types=1);

namespace App\Domain\App\BuildMonthlyStatsHtml;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Domain\Strava\Calendar\Calendar;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\MonthlyStatistics;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildMonthlyStatsHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private ChallengeRepository $challengeRepository,
        private SportTypeRepository $sportTypeRepository,
        private ActivityRepository $activityRepository,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildMonthlyStatsHtml);

        $now = $command->getCurrentDateTime();
        $allActivities = $this->activityRepository->findAll();
        $allChallenges = $this->challengeRepository->findAll();

        $allMonths = Months::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            now: $now
        );

        $monthlyStatistics = MonthlyStatistics::create(
            activities: $allActivities,
            challenges: $allChallenges,
            months: $allMonths,
        );

        $this->buildStorage->write(
            'monthly-stats.html',
            $this->twig->load('html/calendar/monthly-stats.html.twig')->render([
                'monthlyStatistics' => $monthlyStatistics,
                'sportTypes' => $this->sportTypeRepository->findAll(),
            ]),
        );

        /** @var Month $month */
        foreach ($allMonths as $month) {
            $this->buildStorage->write(
                'month/month-'.$month->getId().'.html',
                $this->twig->load('html/calendar/month.html.twig')->render([
                    'hasPreviousMonth' => $month->getId() != $allActivities->getFirstActivityStartDate()->format(Month::MONTH_ID_FORMAT),
                    'hasNextMonth' => $month->getId() != $now->format(Month::MONTH_ID_FORMAT),
                    'statistics' => $monthlyStatistics->getStatisticsForMonth($month),
                    'calendar' => Calendar::create(
                        month: $month,
                        activityRepository: $this->activityRepository,
                    ),
                ]),
            );
        }
    }
}
