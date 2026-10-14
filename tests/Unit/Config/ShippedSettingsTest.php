<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

final class ShippedSettingsTest extends TestCase
{
    private const string MODULE_PATH = __DIR__.'/../../..';

    public function test_shipped_settings_equal_the_banner_defaults(): void
    {
        $this->assertSame($this->bannerDefaults(), $this->shippedSettings());
    }

    public function test_auto_rotate_is_on_by_default(): void
    {
        $this->assertTrue($this->shippedSettings()['auto_rotate'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function shippedSettings(): array
    {
        $settings = require self::MODULE_PATH.'/config/settings.php';
        $this->assertIsArray($settings);
        ksort($settings);

        return $settings;
    }

    /**
     * Fallbacks of `mergedSettings` in AnnouncementBanner.vue, the values the banner
     * uses for any setting it does not receive.
     *
     * @return array<string, bool|int>
     */
    private function bannerDefaults(): array
    {
        $source = (string) file_get_contents(self::MODULE_PATH.'/resources/js/components/AnnouncementBanner.vue');
        preg_match_all('/(\w+): props\.settings\?\.\1 \?\? (true|false|\d+),/', $source, $matches, PREG_SET_ORDER);
        $this->assertNotEmpty($matches, 'No defaults found in AnnouncementBanner.vue');

        $defaults = [];
        foreach ($matches as [, $key, $value]) {
            $defaults[$key] = match ($value) {
                'true' => true,
                'false' => false,
                default => (int) $value,
            };
        }
        ksort($defaults);

        return $defaults;
    }
}
