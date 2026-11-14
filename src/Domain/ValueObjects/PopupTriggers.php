<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * When a window opens. The triggers combine: the first one met opens it. A window with none
 * opens on load (a delay of 0 seconds). Exit intent is for desktop only: on a phone the browser
 * uses the delay instead, or EXIT_INTENT_FALLBACK_SECONDS when there is none.
 */
final readonly class PopupTriggers
{
    public const int DELAY_MIN = 0;

    public const int DELAY_MAX = 60;

    public const int SCROLL_MIN = 10;

    public const int SCROLL_MAX = 100;

    public const int PAGE_VIEWS_MIN = 2;

    public const int PAGE_VIEWS_MAX = 20;

    /** Delay that stands in for exit intent on screens without a mouse, when the window has no delay */
    public const int EXIT_INTENT_FALLBACK_SECONDS = 10;

    public ?int $delaySeconds;

    public function __construct(
        ?int $delaySeconds = null,
        public ?int $scrollPercent = null,
        public bool $exitIntent = false,
        public ?int $pageViews = null,
    ) {
        $this->assertRange('delay', $delaySeconds, self::DELAY_MIN, self::DELAY_MAX);
        $this->assertRange('scroll', $scrollPercent, self::SCROLL_MIN, self::SCROLL_MAX);
        $this->assertRange('page views', $pageViews, self::PAGE_VIEWS_MIN, self::PAGE_VIEWS_MAX);

        $hasAny = $delaySeconds !== null || $scrollPercent !== null || $exitIntent || $pageViews !== null;

        $this->delaySeconds = $hasAny ? $delaySeconds : self::DELAY_MIN;
    }

    private function assertRange(string $name, ?int $value, int $min, int $max): void
    {
        if ($value !== null && ($value < $min || $value > $max)) {
            throw new InvalidArgumentException(sprintf('The %s trigger must be between %d and %d.', $name, $min, $max));
        }
    }
}
