<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Enums;

/**
 * How an announcement is shown on the site: a band in one of the layout positions, or a window
 * over the page (announcements#67).
 */
enum AnnouncementFormat: string
{
    case Banner = 'banner';
    case Popup = 'popup';

    public function label(): string
    {
        return match ($this) {
            self::Banner => __('announcements::announcements.format.banner'),
            self::Popup => __('announcements::announcements.format.popup'),
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
