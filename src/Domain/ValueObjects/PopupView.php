<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\ValueObjects;

use DateTimeImmutable;
use Modules\Announcements\Domain\Enums\PopupFrequency;

/**
 * What an account remembers about one window (announcements#68): when it was last shown to it and
 * when it was closed. Both come from the browser's notices and are instants.
 */
final readonly class PopupView
{
    private const int SECONDS_PER_DAY = 86400;

    public function __construct(
        public ?DateTimeImmutable $lastShownAt = null,
        public ?DateTimeImmutable $dismissedAt = null,
    ) {}

    /**
     * Whether the server still offers the window to an account with this memory (null: it never
     * saw it). Closing counts as seeing it, but never moves the days forward: they count from the
     * last time the window was shown, or from the closing when there is no shown time.
     *
     * «Once per session» is not decided here: the server does not know the browser's sessions, so
     * the browser keeps that one.
     */
    public static function isDue(AnnouncementPopup $popup, ?self $view, DateTimeImmutable $now): bool
    {
        $seenAt = $view instanceof self ? ($view->lastShownAt ?? $view->dismissedAt) : null;

        if (! $seenAt instanceof DateTimeImmutable) {
            return true;
        }

        return match ($popup->frequency) {
            PopupFrequency::Once => false,
            PopupFrequency::Session => true,
            PopupFrequency::Days => $now->getTimestamp() >= $seenAt->getTimestamp() + ($popup->frequencyDays ?? 1) * self::SECONDS_PER_DAY,
        };
    }
}
