<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Enums;

/**
 * What the module counts about a window (announcements#69), anonymously and per day.
 */
enum PopupMetric: string
{
    case Shown = 'shown';
    case Dismissed = 'dismissed';
    case ActionClicked = 'action_clicked';

    /**
     * The values as a route constraint.
     */
    public static function routePattern(): string
    {
        return implode('|', array_map(static fn (self $metric): string => $metric->value, self::cases()));
    }
}
