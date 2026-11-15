<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\Enums;

use Modules\Announcements\Domain\Enums\PopupFrequency;
use PHPUnit\Framework\TestCase;

final class PopupFrequencyTest extends TestCase
{
    public function test_only_the_days_frequency_needs_a_number_of_days(): void
    {
        $this->assertFalse(PopupFrequency::Once->needsDays());
        $this->assertFalse(PopupFrequency::Session->needsDays());
        $this->assertTrue(PopupFrequency::Days->needsDays());
    }
}
