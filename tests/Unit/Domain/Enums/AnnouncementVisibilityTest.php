<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\Enums;

use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use PHPUnit\Framework\TestCase;
use stdClass;

final class AnnouncementVisibilityTest extends TestCase
{
    public function test_visibility_levels_keep_their_stored_values(): void
    {
        $this->assertEquals('public', AnnouncementVisibility::Public->value);
        $this->assertEquals('authenticated', AnnouncementVisibility::Authenticated->value);
        $this->assertEquals('members', AnnouncementVisibility::Members->value);
        $this->assertEquals('guests', AnnouncementVisibility::Guests->value);
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

    public function test_guests_only_is_visible_to_visitors_without_a_session(): void
    {
        $this->assertTrue(AnnouncementVisibility::Guests->isVisibleTo(null));
        $this->assertFalse(AnnouncementVisibility::Guests->isVisibleTo(new stdClass));
        $this->assertFalse(AnnouncementVisibility::Guests->isVisibleTo(new stdClass, isMember: true));
    }

    public function test_only_members_only_is_closed_to_anybody(): void
    {
        // Anybody may sign up or sign out to see the others; only members see a members-only one
        $this->assertTrue(AnnouncementVisibility::Public->isOpenToAnybody());
        $this->assertTrue(AnnouncementVisibility::Authenticated->isOpenToAnybody());
        $this->assertTrue(AnnouncementVisibility::Guests->isOpenToAnybody());
        $this->assertFalse(AnnouncementVisibility::Members->isOpenToAnybody());
    }
}
