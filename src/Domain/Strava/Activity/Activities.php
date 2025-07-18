<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;

/**
 * @extends Collection<\App\Domain\Strava\Activity\Activity>
 */
final class Activities extends Collection
{
    public function getItemClassName(): string
    {
        return Activity::class;
    }

    public function getFirstActivityStartDate(): SerializableDateTime
    {
        $startDate = null;
        foreach ($this as $activity) {
            if ($startDate && $activity->getStartDate()->isAfterOrOn($startDate)) {
                continue;
            }
            $startDate = $activity->getStartDate();
        }

        if (!$startDate) {
            throw new \RuntimeException('No activities found');
        }

        return $startDate;
    }

    public function filterOnActivityType(ActivityType $activityType): Activities
    {
        return $this->filter(fn (Activity $activity) => $activityType === $activity->getSportType()->getActivityType());
    }

    public function filterOnSportType(SportType $sportType): Activities
    {
        return $this->filter(fn (Activity $activity) => $sportType === $activity->getSportType());
    }

    public function getUniqueYears(): Years
    {
        $years = Years::empty();
        /** @var Activity $activity */
        foreach ($this as $activity) {
            $activityYear = Year::fromInt($activity->getStartDate()->getYear());
            if ($years->has($activityYear)) {
                continue;
            }
            $years->add($activityYear);
        }

        return $years;
    }
}
