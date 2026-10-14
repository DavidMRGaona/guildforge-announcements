<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\Enums;

use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use PHPUnit\Framework\TestCase;
use stdClass;

final class AnnouncementVisibilityTest extends TestCase
{
    public function test_it_has_three_visibility_levels(): void
    {
        $cases = AnnouncementVisibility::cases();

        $this->assertCount(3, $cases);
        $this->assertEquals('public', AnnouncementVisibility::Public->value);
        $this->assertEquals('authenticated', AnnouncementVisibility::Authenticated->value);
        $this->assertEquals('members', AnnouncementVisibility::Members->value);
    }

    public function test_public_is_visible_to_guest(): void
    {
        $this->assertTrue(AnnouncementVisibility::Public->isVisibleTo(null));
    }

    public function test_public_is_visible_to_authenticated_user(): void
    {
        $this->assertTrue(AnnouncementVisibility::Public->isVisibleTo(new stdClass));
    }

    public function test_public_is_visible_to_member(): void
    {
        $this->assertTrue(AnnouncementVisibility::Public->isVisibleTo(new stdClass, isMember: true));
    }

    public function test_authenticated_is_not_visible_to_guest(): void
    {
        $this->assertFalse(AnnouncementVisibility::Authenticated->isVisibleTo(null));
    }

    public function test_authenticated_is_visible_to_authenticated_user(): void
    {
        $this->assertTrue(AnnouncementVisibility::Authenticated->isVisibleTo(new stdClass));
    }

    public function test_authenticated_is_visible_to_member(): void
    {
        $this->assertTrue(AnnouncementVisibility::Authenticated->isVisibleTo(new stdClass, isMember: true));
    }

    public function test_members_is_visible_to_a_member(): void
    {
        $this->assertTrue(AnnouncementVisibility::Members->isVisibleTo(new stdClass, isMember: true));
    }

    public function test_members_is_not_visible_to_a_non_member(): void
    {
        $this->assertFalse(AnnouncementVisibility::Members->isVisibleTo(new stdClass, isMember: false));
    }

    public function test_members_is_not_visible_to_a_guest_even_if_flagged(): void
    {
        $this->assertFalse(AnnouncementVisibility::Members->isVisibleTo(null, isMember: true));
    }

    public function test_members_ignores_an_is_member_attribute_on_the_user(): void
    {
        $user = new stdClass;
        $user->is_member = true;

        $this->assertFalse(AnnouncementVisibility::Members->isVisibleTo($user));
    }
}
