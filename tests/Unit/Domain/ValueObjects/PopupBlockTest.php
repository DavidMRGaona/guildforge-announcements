<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPopup;
use Modules\Announcements\Domain\ValueObjects\PopupBlock;
use PHPUnit\Framework\TestCase;

final class PopupBlockTest extends TestCase
{
    public function test_it_keeps_the_key_and_the_settings(): void
    {
        $block = new PopupBlock(' newsletter.subscribe ', ['heading' => 'Suscríbete', 'list' => 3, 'compact' => true, 'note' => null]);

        $this->assertSame('newsletter.subscribe', $block->key);
        $this->assertSame(['heading' => 'Suscríbete', 'list' => 3, 'compact' => true, 'note' => null], $block->settings);
    }

    public function test_a_block_without_a_key_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PopupBlock('  ');
    }

    public function test_a_key_longer_than_a_site_block_key_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PopupBlock(str_repeat('a', 101));
    }

    public function test_settings_must_be_named_by_text(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PopupBlock('newsletter.subscribe', ['one', 'two']);
    }

    public function test_settings_must_survive_json(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PopupBlock('newsletter.subscribe', ['broken' => NAN]);
    }

    public function test_a_window_has_no_block_and_hides_without_one_by_default(): void
    {
        $popup = new AnnouncementPopup;

        $this->assertNull($popup->block);
        $this->assertTrue($popup->hideWhenBlockUnavailable);
    }

    public function test_a_window_keeps_its_block_and_whether_it_hides_without_it(): void
    {
        $popup = new AnnouncementPopup(block: new PopupBlock('newsletter.subscribe'), hideWhenBlockUnavailable: false);

        $this->assertSame('newsletter.subscribe', $popup->block?->key);
        $this->assertFalse($popup->hideWhenBlockUnavailable);
    }
}
