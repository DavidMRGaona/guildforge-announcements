<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Lang;

use PHPUnit\Framework\TestCase;

/**
 * The English panel texts had fallen behind the Spanish ones and showed raw keys: both files
 * hold the same keys.
 */
final class LocaleKeysTest extends TestCase
{
    public function test_spanish_and_english_hold_the_same_keys(): void
    {
        $this->assertSame($this->keys('es'), $this->keys('en'));
    }

    /**
     * @return list<string>
     */
    private function keys(string $locale): array
    {
        /** @var array<string, mixed> $lines */
        $lines = require dirname(__DIR__, 3)."/lang/{$locale}/announcements.php";

        $keys = $this->flatten($lines);
        sort($keys);

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $lines
     * @return list<string>
     */
    private function flatten(array $lines, string $prefix = ''): array
    {
        $keys = [];

        foreach ($lines as $key => $value) {
            $name = $prefix.$key;
            $keys = [...$keys, ...(is_array($value) ? $this->flatten($value, $name.'.') : [$name])];
        }

        return $keys;
    }
}
