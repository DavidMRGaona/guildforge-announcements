<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\ValueObjects;

use DateTimeImmutable;
use Modules\Announcements\Domain\Enums\PopupFrequency;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPopup;
use Modules\Announcements\Domain\ValueObjects\PopupView;
use PHPUnit\Framework\TestCase;

/**
 * What an account remembers about a window (announcements#68) against the frequency of the
 * window: whether the server still offers it.
 */
final class PopupViewTest extends TestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-11-15 10:00:00 UTC');
    }

    public function test_a_window_the_account_never_saw_is_always_offered(): void
    {
        foreach ([PopupFrequency::Once, PopupFrequency::Session, PopupFrequency::Days] as $frequency) {
            $popup = new AnnouncementPopup(frequency: $frequency, frequencyDays: $frequency->needsDays() ? 7 : null);

            $this->assertTrue(PopupView::isDue($popup, null, $this->now));
        }
    }

    public function test_a_window_shown_once_is_never_offered_again(): void
    {
        $popup = new AnnouncementPopup(frequency: PopupFrequency::Once);
        $view = new PopupView(lastShownAt: $this->now->modify('-400 days'));

        $this->assertFalse(PopupView::isDue($popup, $view, $this->now));
    }

    public function test_closing_a_window_counts_as_seeing_it(): void
    {
        $popup = new AnnouncementPopup(frequency: PopupFrequency::Once);
        $view = new PopupView(dismissedAt: $this->now->modify('-1 day'));

        $this->assertFalse(PopupView::isDue($popup, $view, $this->now));
    }

    public function test_a_window_once_per_session_is_left_to_the_browser(): void
    {
        // The server does not know the browser's sessions: it keeps offering the window
        $popup = new AnnouncementPopup(frequency: PopupFrequency::Session);
        $view = new PopupView(lastShownAt: $this->now->modify('-1 minute'));

        $this->assertTrue(PopupView::isDue($popup, $view, $this->now));
    }

    public function test_a_window_every_n_days_returns_when_n_days_have_passed_since_it_was_last_shown(): void
    {
        $popup = new AnnouncementPopup(frequency: PopupFrequency::Days, frequencyDays: 7);

        $this->assertFalse(PopupView::isDue($popup, new PopupView(lastShownAt: $this->now->modify('-6 days -23 hours')), $this->now));
        $this->assertTrue(PopupView::isDue($popup, new PopupView(lastShownAt: $this->now->modify('-7 days')), $this->now));
        $this->assertTrue(PopupView::isDue($popup, new PopupView(lastShownAt: $this->now->modify('-30 days')), $this->now));
    }

    public function test_closing_does_not_move_the_days_forward(): void
    {
        // Closed two days after it was shown: it still returns seven days after being shown
        $popup = new AnnouncementPopup(frequency: PopupFrequency::Days, frequencyDays: 7);
        $view = new PopupView(
            lastShownAt: $this->now->modify('-7 days'),
            dismissedAt: $this->now->modify('-5 days'),
        );

        $this->assertTrue(PopupView::isDue($popup, $view, $this->now));
    }

    public function test_a_window_closed_without_a_shown_time_counts_from_when_it_was_closed(): void
    {
        $popup = new AnnouncementPopup(frequency: PopupFrequency::Days, frequencyDays: 7);
        $view = new PopupView(dismissedAt: $this->now->modify('-2 days'));

        $this->assertFalse(PopupView::isDue($popup, $view, $this->now));
    }
}
