<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Enums;

/**
 * Where a window may open. The browser decides it, and it never opens on the pages the module
 * always leaves out (panel, sign-in, legal pages, maintenance, newsletter, errors).
 */
enum PopupPages: string
{
    case All = 'all';
    case Home = 'home';
    case Paths = 'paths';

    public function label(): string
    {
        return match ($this) {
            self::All => __('announcements::announcements.popup.pages.all'),
            self::Home => __('announcements::announcements.popup.pages.home'),
            self::Paths => __('announcements::announcements.popup.pages.paths'),
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
