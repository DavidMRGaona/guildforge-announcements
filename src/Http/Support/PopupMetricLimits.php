<?php

declare(strict_types=1);

namespace Modules\Announcements\Http\Support;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Limits of the anonymous counters (announcements#69). The route is open to anyone, so the
 * counters can be inflated: these limits make it slow, not impossible (see the README).
 */
final class PopupMetricLimits
{
    public const string PER_WINDOW = 'announcement-popup-metric';

    public const string PER_ADDRESS = 'announcement-popup-metrics-ip';

    /** The same event on the same window, per account or address */
    public const int PER_WINDOW_PER_MINUTE = 5;

    /** Every counter together, per address */
    public const int PER_ADDRESS_PER_MINUTE = 30;

    public static function register(): void
    {
        RateLimiter::for(self::PER_WINDOW, static function (Request $request): Limit {
            $who = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(self::PER_WINDOW_PER_MINUTE)->by(sprintf(
                '%s|%s|%s',
                is_scalar($who) ? (string) $who : '',
                (string) $request->route('id'),
                (string) $request->route('metric'),
            ));
        });

        RateLimiter::for(self::PER_ADDRESS, static fn (Request $request): Limit => Limit::perMinute(self::PER_ADDRESS_PER_MINUTE)->by((string) $request->ip()));
    }
}
