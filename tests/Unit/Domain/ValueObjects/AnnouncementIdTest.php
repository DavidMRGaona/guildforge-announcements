<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;
use PHPUnit\Framework\TestCase;

final class AnnouncementIdTest extends TestCase
{
    public function test_it_generates_valid_uuid(): void
    {
        $id = AnnouncementId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $id->value
        );
    }

    public function test_it_creates_from_string(): void
    {
        $uuid = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';

        $id = AnnouncementId::fromString($uuid);

        $this->assertEquals($uuid, $id->value);
    }

    public function test_it_throws_exception_for_invalid_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AnnouncementId::fromString('invalid-uuid');
    }

    public function test_it_converts_to_string(): void
    {
        $uuid = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $id = AnnouncementId::fromString($uuid);

        $this->assertEquals($uuid, (string) $id);
    }

    public function test_it_compares_equality(): void
    {
        $uuid = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $id1 = AnnouncementId::fromString($uuid);
        $id2 = AnnouncementId::fromString($uuid);
        $id3 = AnnouncementId::generate();

        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }
}
