<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPriority;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AnnouncementPriorityTest extends TestCase
{
    public function test_it_creates_with_valid_priority(): void
    {
        $priority = new AnnouncementPriority(5);

        $this->assertEquals(5, $priority->value);
    }

    public function test_it_creates_default_priority(): void
    {
        $priority = AnnouncementPriority::default();

        $this->assertEquals(5, $priority->value);
    }

    #[DataProvider('validPriorityProvider')]
    public function test_it_accepts_valid_priorities(int $value): void
    {
        $priority = new AnnouncementPriority($value);

        $this->assertEquals($value, $priority->value);
    }

    public static function validPriorityProvider(): array
    {
        return [
            'minimum' => [1],
            'low' => [2],
            'default' => [5],
            'high' => [8],
            'maximum' => [10],
        ];
    }

    #[DataProvider('invalidPriorityProvider')]
    public function test_it_throws_exception_for_invalid_priority(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Priority must be between 1 and 10');

        new AnnouncementPriority($value);
    }

    public static function invalidPriorityProvider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
            'too high' => [11],
            'very high' => [100],
        ];
    }

    public function test_it_converts_to_int(): void
    {
        $priority = new AnnouncementPriority(7);

        $this->assertEquals(7, $priority->toInt());
    }

    public function test_it_compares_equality(): void
    {
        $priority1 = new AnnouncementPriority(5);
        $priority2 = new AnnouncementPriority(5);
        $priority3 = new AnnouncementPriority(8);

        $this->assertTrue($priority1->equals($priority2));
        $this->assertFalse($priority1->equals($priority3));
    }

    public function test_it_compares_greater_than(): void
    {
        $high = new AnnouncementPriority(8);
        $low = new AnnouncementPriority(3);

        $this->assertTrue($high->isHigherThan($low));
        $this->assertFalse($low->isHigherThan($high));
        $this->assertFalse($high->isHigherThan($high));
    }

    public function test_it_compares_less_than(): void
    {
        $high = new AnnouncementPriority(8);
        $low = new AnnouncementPriority(3);

        $this->assertTrue($low->isLowerThan($high));
        $this->assertFalse($high->isLowerThan($low));
        $this->assertFalse($low->isLowerThan($low));
    }
}
