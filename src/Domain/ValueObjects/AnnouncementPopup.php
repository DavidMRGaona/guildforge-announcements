<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Announcements\Domain\Enums\PopupDevice;
use Modules\Announcements\Domain\Enums\PopupPages;
use Modules\Announcements\Domain\Enums\PopupShape;

/**
 * How an announcement in the popup format opens (announcements#67). The server decides who is
 * offered it (visibility and dates of the announcement); the browser applies the triggers, the
 * pages and the device.
 */
final readonly class AnnouncementPopup
{
    public const int MAX_PATHS = 30;

    public const int IMAGE_ALT_MAX_LENGTH = 255;

    public ?string $imagePublicId;

    /** What the image shows, for people who cannot see it; null when it is only decoration */
    public ?string $imageAlt;

    /** @var list<string> */
    public array $paths;

    /**
     * @param  list<string>  $paths  Kept only when the window is limited to them
     */
    public function __construct(
        public PopupShape $shape = PopupShape::Centered,
        ?string $imagePublicId = null,
        public ?PopupAction $action = null,
        public PopupTriggers $triggers = new PopupTriggers,
        public PopupPages $pages = PopupPages::All,
        array $paths = [],
        public PopupDevice $device = PopupDevice::All,
        ?string $imageAlt = null,
    ) {
        $imagePublicId = $imagePublicId !== null ? trim($imagePublicId) : null;
        $this->imagePublicId = $imagePublicId === '' ? null : $imagePublicId;

        $imageAlt = $imageAlt !== null ? trim($imageAlt) : null;

        if ($imageAlt !== null && mb_strlen($imageAlt) > self::IMAGE_ALT_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('The text of the image of a window has at most %d characters.', self::IMAGE_ALT_MAX_LENGTH));
        }

        $this->imageAlt = $imageAlt === '' ? null : $imageAlt;
        $this->paths = $pages === PopupPages::Paths ? $this->validPaths($paths) : [];
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function validPaths(array $paths): array
    {
        if ($paths === [] || count($paths) > self::MAX_PATHS) {
            throw new InvalidArgumentException(sprintf('A window limited to some pages needs between 1 and %d paths.', self::MAX_PATHS));
        }

        foreach ($paths as $path) {
            if (! PopupPagePattern::isValid($path)) {
                throw new InvalidArgumentException('A page of a window must be a path starting with "/", with an optional final "*".');
            }
        }

        return array_values(array_unique($paths));
    }
}
