<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * The button of a window: a short text and a link, either an https address or a path of this
 * site ("/eventos"). Anything else (plain http, javascript:, //host) is refused, so the link can
 * never run a script or leave the site over an unencrypted connection.
 */
final readonly class PopupAction
{
    public const int LABEL_MAX_LENGTH = 60;

    public const int URL_MAX_LENGTH = 2048;

    public string $label;

    public function __construct(
        string $label,
        public string $url,
    ) {
        $label = trim($label);

        if ($label === '' || mb_strlen($label) > self::LABEL_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('The button text must have between 1 and %d characters.', self::LABEL_MAX_LENGTH));
        }

        if (! self::isValidUrl($url)) {
            throw new InvalidArgumentException('The button link must be an https address or a path starting with "/".');
        }

        $this->label = $label;
    }

    public static function isValidUrl(string $url): bool
    {
        if ($url === '' || strlen($url) > self::URL_MAX_LENGTH || preg_match('/[\s\x00-\x1F\x7F\\\\]/', $url) === 1) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);

        return is_array($parts)
            && strtolower($parts['scheme'] ?? '') === 'https'
            && ($parts['host'] ?? '') !== '';
    }
}
