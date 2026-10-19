<?php

namespace App\Modules\Calendarios\Services;

class Schedule
{
    public static function datedMatches(array $games): array
    {
        $scheduled = [];
        foreach ($games as $match) {
            if (!empty($match['scheduled_at'])) {
                $scheduled[] = $match;
            }
        }
        usort($scheduled, [self::class, 'compareDates']);
        return $scheduled;
    }

    public static function compareDates(array $firstMatch, array $secondMatch): int
    {
        return strcmp($firstMatch['scheduled_at'], $secondMatch['scheduled_at']);
    }
}
