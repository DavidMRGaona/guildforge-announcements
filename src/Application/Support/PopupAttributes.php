<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\Support;

use BackedEnum;
use InvalidArgumentException;
use Modules\Announcements\Domain\Enums\PopupDevice;
use Modules\Announcements\Domain\Enums\PopupPages;
use Modules\Announcements\Domain\Enums\PopupShape;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPopup;
use Modules\Announcements\Domain\ValueObjects\PopupAction;
use Modules\Announcements\Domain\ValueObjects\PopupTriggers;

/**
 * The window settings as stored in the announcements table (and submitted by the panel form, which
 * uses the same names), turned into the value object and back.
 */
final class PopupAttributes
{
    /** @var list<string> */
    public const array COLUMNS = [
        'popup_shape',
        'popup_image_public_id',
        'popup_action_label',
        'popup_action_url',
        'popup_trigger_delay',
        'popup_trigger_scroll',
        'popup_trigger_exit_intent',
        'popup_trigger_page_views',
        'popup_pages',
        'popup_paths',
        'popup_device',
    ];

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidArgumentException when a value is not one a window can have
     */
    public static function toPopup(array $attributes): AnnouncementPopup
    {
        return new AnnouncementPopup(
            shape: PopupShape::tryFrom(self::enumValue($attributes['popup_shape'] ?? null, PopupShape::Centered->value))
                ?? throw new InvalidArgumentException('Unknown window shape.'),
            imagePublicId: self::stringOrNull($attributes['popup_image_public_id'] ?? null),
            action: self::action($attributes),
            triggers: new PopupTriggers(
                delaySeconds: self::intOrNull($attributes['popup_trigger_delay'] ?? null),
                scrollPercent: self::intOrNull($attributes['popup_trigger_scroll'] ?? null),
                exitIntent: (bool) ($attributes['popup_trigger_exit_intent'] ?? false),
                pageViews: self::intOrNull($attributes['popup_trigger_page_views'] ?? null),
            ),
            pages: PopupPages::tryFrom(self::enumValue($attributes['popup_pages'] ?? null, PopupPages::All->value))
                ?? throw new InvalidArgumentException('Unknown window pages.'),
            paths: self::paths($attributes['popup_paths'] ?? null),
            device: PopupDevice::tryFrom(self::enumValue($attributes['popup_device'] ?? null, PopupDevice::All->value))
                ?? throw new InvalidArgumentException('Unknown window device.'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toAttributes(AnnouncementPopup $popup): array
    {
        return [
            'popup_shape' => $popup->shape->value,
            'popup_image_public_id' => $popup->imagePublicId,
            'popup_action_label' => $popup->action?->label,
            'popup_action_url' => $popup->action?->url,
            'popup_trigger_delay' => $popup->triggers->delaySeconds,
            'popup_trigger_scroll' => $popup->triggers->scrollPercent,
            'popup_trigger_exit_intent' => $popup->triggers->exitIntent,
            'popup_trigger_page_views' => $popup->triggers->pageViews,
            'popup_pages' => $popup->pages->value,
            'popup_paths' => $popup->paths === [] ? null : $popup->paths,
            'popup_device' => $popup->device->value,
        ];
    }

    /**
     * A button needs both its text and its link; with only one of them there is no button.
     *
     * @param  array<string, mixed>  $attributes
     */
    private static function action(array $attributes): ?PopupAction
    {
        $label = self::stringOrNull($attributes['popup_action_label'] ?? null);
        $url = self::stringOrNull($attributes['popup_action_url'] ?? null);

        return $label !== null && $url !== null ? new PopupAction($label, $url) : null;
    }

    private static function enumValue(mixed $value, string $default): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            return (int) trim($value);
        }

        throw new InvalidArgumentException('A window trigger must be a whole number.');
    }

    /**
     * @return list<string>
     */
    private static function paths(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException('The pages of a window must be a list.');
        }

        $paths = [];

        foreach ($value as $path) {
            if (! is_string($path)) {
                throw new InvalidArgumentException('Every page of a window must be a path.');
            }

            $path = trim($path);

            if ($path !== '') {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}
