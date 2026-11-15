<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\ValueObjects;

use InvalidArgumentException;
use JsonException;

/**
 * The site block a window carries (announcements#70): which one (`<module>.<block>` or
 * `core.<block>`) and the settings the administration filled for it. This module knows nothing
 * else about blocks: the core says which exist and how to draw them.
 */
final readonly class PopupBlock
{
    public const int KEY_MAX_LENGTH = 100;

    public string $key;

    /**
     * @param  array<array-key, mixed>  $settings  Must be named by text and able to be kept as JSON (checked here)
     */
    public function __construct(
        string $key,
        /** @var array<array-key, mixed> */
        public array $settings = [],
    ) {
        $key = trim($key);

        if ($key === '' || mb_strlen($key) > self::KEY_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('The block of a window needs a key of at most %d characters.', self::KEY_MAX_LENGTH));
        }

        $this->key = $key;

        foreach (array_keys($settings) as $name) {
            if (! is_string($name)) {
                throw new InvalidArgumentException('The settings of the block of a window must be named by text.');
            }
        }

        try {
            json_encode($settings, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('The settings of the block of a window must be able to be kept as JSON.');
        }
    }
}
