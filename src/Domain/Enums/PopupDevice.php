<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Enums;

/**
 * Which screens a window opens on: phones are screens under 768 px wide, the rest are desktop.
 */
enum PopupDevice: string
{
    case All = 'all';
    case Mobile = 'mobile';
    case Desktop = 'desktop';

    public function label(): string
    {
        return match ($this) {
            self::All => __('announcements::announcements.popup.device.all'),
            self::Mobile => __('announcements::announcements.popup.device.mobile'),
            self::Desktop => __('announcements::announcements.popup.device.desktop'),
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
