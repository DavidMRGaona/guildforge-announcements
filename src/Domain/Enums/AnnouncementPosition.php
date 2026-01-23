<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Enums;

enum AnnouncementPosition: string
{
    case BeforeHeader = 'before-header';
    case AfterHeader = 'after-header';
    case BeforeContent = 'before-content';
    case AfterContent = 'after-content';
    case BeforeFooter = 'before-footer';
    case AfterFooter = 'after-footer';

    public function label(): string
    {
        return match ($this) {
            self::BeforeHeader => __('announcements::announcements.position.before_header'),
            self::AfterHeader => __('announcements::announcements.position.after_header'),
            self::BeforeContent => __('announcements::announcements.position.before_content'),
            self::AfterContent => __('announcements::announcements.position.after_content'),
            self::BeforeFooter => __('announcements::announcements.position.before_footer'),
            self::AfterFooter => __('announcements::announcements.position.after_footer'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::BeforeHeader->value => __('announcements::announcements.position.before_header'),
            self::AfterHeader->value => __('announcements::announcements.position.after_header'),
            self::BeforeContent->value => __('announcements::announcements.position.before_content'),
            self::AfterContent->value => __('announcements::announcements.position.after_content'),
            self::BeforeFooter->value => __('announcements::announcements.position.before_footer'),
            self::AfterFooter->value => __('announcements::announcements.position.after_footer'),
        ];
    }
}
