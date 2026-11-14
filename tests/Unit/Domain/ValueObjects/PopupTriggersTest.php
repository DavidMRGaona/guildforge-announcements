<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Announcements\Domain\ValueObjects\PopupTriggers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PopupTriggersTest extends TestCase
{
    public function test_it_keeps_every_trigger_it_is_given(): void
    {
        $triggers = new PopupTriggers(delaySeconds: 5, scrollPercent: 50, exitIntent: true, pageViews: 3);

        $this->assertSame(5, $triggers->delaySeconds);
        $this->assertSame(50, $triggers->scrollPercent);
        $this->assertTrue($triggers->exitIntent);
        $this->assertSame(3, $triggers->pageViews);
    }

    public function test_without_any_trigger_it_opens_on_load(): void
    {
        $triggers = new PopupTriggers;

        $this->assertSame(0, $triggers->delaySeconds);
        $this->assertNull($triggers->scrollPercent);
        $this->assertFalse($triggers->exitIntent);
        $this->assertNull($triggers->pageViews);
    }

    public function test_a_delay_of_zero_is_a_trigger_of_its_own(): void
    {
        $triggers = new PopupTriggers(delaySeconds: 0, scrollPercent: 30);

        $this->assertSame(0, $triggers->delaySeconds);
        $this->assertSame(30, $triggers->scrollPercent);
    }

    public function test_another_trigger_alone_does_not_add_a_delay(): void
    {
        $this->assertNull((new PopupTriggers(scrollPercent: 30))->delaySeconds);
        $this->assertNull((new PopupTriggers(exitIntent: true))->delaySeconds);
        $this->assertNull((new PopupTriggers(pageViews: 2))->delaySeconds);
    }

    /**
     * @return array<string, array{?int, ?int, ?int}>
     */
    public static function outOfRange(): array
    {
        return [
            'negative delay' => [-1, null, null],
            'delay over a minute' => [61, null, null],
            'scroll under 10 %' => [null, 9, null],
            'scroll over 100 %' => [null, 101, null],
            'one page view' => [null, null, 1],
            'over 20 page views' => [null, null, 21],
        ];
    }

    #[DataProvider('outOfRange')]
    public function test_it_refuses_values_out_of_range(?int $delay, ?int $scroll, ?int $pageViews): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PopupTriggers(delaySeconds: $delay, scrollPercent: $scroll, pageViews: $pageViews);
    }

    public function test_it_accepts_the_bounds(): void
    {
        $low = new PopupTriggers(delaySeconds: 0, scrollPercent: 10, pageViews: 2);
        $high = new PopupTriggers(delaySeconds: 60, scrollPercent: 100, pageViews: 20);

        $this->assertSame([0, 10, 2], [$low->delaySeconds, $low->scrollPercent, $low->pageViews]);
        $this->assertSame([60, 100, 20], [$high->delaySeconds, $high->scrollPercent, $high->pageViews]);
    }
}
