<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Enums;

/**
 * A centred window blocks the page until it is closed (modal); a corner card sits at the bottom
 * and lets the visitor go on reading.
 */
enum PopupShape: string
{
    case Centered = 'centered';
    case Corner = 'corner';

    public function label(): string
    {
        return match ($this) {
            self::Centered => __('announcements::announcements.popup.shape.centered'),
            self::Corner => __('announcements::announcements.popup.shape.corner'),
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
