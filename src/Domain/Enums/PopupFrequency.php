<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Enums;

/**
 * How often a window comes back to the same person (announcements#68). Opening it and closing it
 * both count as having seen it.
 */
enum PopupFrequency: string
{
    /** Never again once it was seen */
    case Once = 'once';

    /** Again in the next browser session (the tab or window is closed and opened anew) */
    case Session = 'session';

    /** Again when a number of days has passed since it was last shown */
    case Days = 'days';

    public function needsDays(): bool
    {
        return $this === self::Days;
    }

    public function label(): string
    {
        return match ($this) {
            self::Once => __('announcements::announcements.popup.frequency.once'),
            self::Session => __('announcements::announcements.popup.frequency.session'),
            self::Days => __('announcements::announcements.popup.frequency.days'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
