<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Announcements\Domain\Enums\PopupDevice;
use Modules\Announcements\Domain\Enums\PopupFrequency;
use Modules\Announcements\Domain\Enums\PopupPages;
use Modules\Announcements\Domain\Enums\PopupShape;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPopup;
use Modules\Announcements\Domain\ValueObjects\PopupAction;
use Modules\Announcements\Domain\ValueObjects\PopupTriggers;
use PHPUnit\Framework\TestCase;

final class AnnouncementPopupTest extends TestCase
{
    public function test_the_defaults_are_a_centred_window_on_every_page_and_device_on_load(): void
    {
        $popup = new AnnouncementPopup;

        $this->assertSame(PopupShape::Centered, $popup->shape);
        $this->assertNull($popup->imagePublicId);
        $this->assertNull($popup->action);
        $this->assertSame(0, $popup->triggers->delaySeconds);
        $this->assertSame(PopupPages::All, $popup->pages);
        $this->assertSame([], $popup->paths);
        $this->assertSame(PopupDevice::All, $popup->device);
    }

    public function test_it_keeps_what_it_is_given(): void
    {
        $popup = new AnnouncementPopup(
            shape: PopupShape::Corner,
            imagePublicId: 'announcements/popups/2026/11/a.webp',
            action: new PopupAction('Hazte socio', '/socios'),
            triggers: new PopupTriggers(scrollPercent: 40),
            pages: PopupPages::Paths,
            paths: ['/eventos*', '/articulos'],
            device: PopupDevice::Mobile,
        );

        $this->assertSame(PopupShape::Corner, $popup->shape);
        $this->assertSame('announcements/popups/2026/11/a.webp', $popup->imagePublicId);
        $this->assertSame('/socios', $popup->action?->url);
        $this->assertSame(40, $popup->triggers->scrollPercent);
        $this->assertSame(['/eventos*', '/articulos'], $popup->paths);
        $this->assertSame(PopupDevice::Mobile, $popup->device);
    }

    public function test_paths_are_only_kept_when_the_window_is_limited_to_them(): void
    {
        $popup = new AnnouncementPopup(pages: PopupPages::Home, paths: ['/eventos']);

        $this->assertSame([], $popup->paths);
    }

    public function test_a_window_limited_to_paths_needs_at_least_one(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AnnouncementPopup(pages: PopupPages::Paths, paths: []);
    }

    public function test_it_refuses_an_invalid_path(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AnnouncementPopup(pages: PopupPages::Paths, paths: ['/eventos', 'https://example.org']);
    }

    public function test_it_refuses_too_many_paths(): void
    {
        $paths = array_map(static fn (int $i): string => '/pagina-'.$i, range(1, AnnouncementPopup::MAX_PATHS + 1));

        $this->expectException(InvalidArgumentException::class);

        new AnnouncementPopup(pages: PopupPages::Paths, paths: $paths);
    }

    public function test_the_image_text_is_trimmed_blank_means_decorative_and_has_a_limit(): void
    {
        $this->assertSame('Dados', (new AnnouncementPopup(imageAlt: ' Dados '))->imageAlt);
        $this->assertNull((new AnnouncementPopup(imageAlt: '  '))->imageAlt);
        $this->assertNull((new AnnouncementPopup)->imageAlt);

        $this->expectException(InvalidArgumentException::class);
        new AnnouncementPopup(imageAlt: str_repeat('a', AnnouncementPopup::IMAGE_ALT_MAX_LENGTH + 1));
    }

    public function test_a_blank_image_is_no_image(): void
    {
        $this->assertNull((new AnnouncementPopup(imagePublicId: '  '))->imagePublicId);
    }

    public function test_by_default_a_window_opens_once(): void
    {
        $popup = new AnnouncementPopup;

        $this->assertSame(PopupFrequency::Once, $popup->frequency);
        $this->assertNull($popup->frequencyDays);
    }

    public function test_a_window_every_n_days_keeps_the_days(): void
    {
        $popup = new AnnouncementPopup(frequency: PopupFrequency::Days, frequencyDays: 30);

        $this->assertSame(PopupFrequency::Days, $popup->frequency);
        $this->assertSame(30, $popup->frequencyDays);
    }

    public function test_the_days_are_only_kept_when_the_window_returns_every_n_days(): void
    {
        $popup = new AnnouncementPopup(frequency: PopupFrequency::Session, frequencyDays: 30);

        $this->assertNull($popup->frequencyDays);
    }

    public function test_a_window_every_n_days_needs_the_days(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AnnouncementPopup(frequency: PopupFrequency::Days);
    }

    public function test_the_days_are_between_one_and_a_year(): void
    {
        $this->assertSame(1, (new AnnouncementPopup(frequency: PopupFrequency::Days, frequencyDays: 1))->frequencyDays);
        $this->assertSame(365, (new AnnouncementPopup(frequency: PopupFrequency::Days, frequencyDays: 365))->frequencyDays);

        foreach ([0, -3, 366] as $days) {
            try {
                new AnnouncementPopup(frequency: PopupFrequency::Days, frequencyDays: $days);
                $this->fail("{$days} days must be refused");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
