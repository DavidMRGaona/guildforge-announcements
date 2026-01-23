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
        $visibility = AnnouncementVisibility::Public;

        $this->assertTrue($visibility->isVisibleTo(null));
    }

    public function test_public_is_visible_to_authenticated_user(): void
    {
        $visibility = AnnouncementVisibility::Public;
        $user = $this->createUser();

        $this->assertTrue($visibility->isVisibleTo($user));
    }

    public function test_public_is_visible_to_member(): void
    {
        $visibility = AnnouncementVisibility::Public;
        $user = $this->createUser(isMember: true);

        $this->assertTrue($visibility->isVisibleTo($user));
    }

    public function test_authenticated_is_not_visible_to_guest(): void
    {
        $visibility = AnnouncementVisibility::Authenticated;

        $this->assertFalse($visibility->isVisibleTo(null));
    }

    public function test_authenticated_is_visible_to_authenticated_user(): void
    {
        $visibility = AnnouncementVisibility::Authenticated;
        $user = $this->createUser();

        $this->assertTrue($visibility->isVisibleTo($user));
    }

    public function test_authenticated_is_visible_to_member(): void
    {
        $visibility = AnnouncementVisibility::Authenticated;
        $user = $this->createUser(isMember: true);

        $this->assertTrue($visibility->isVisibleTo($user));
    }

    public function test_members_is_not_visible_to_guest(): void
    {
        $visibility = AnnouncementVisibility::Members;

        $this->assertFalse($visibility->isVisibleTo(null));
    }

    public function test_members_is_not_visible_to_regular_authenticated_user(): void
    {
        $visibility = AnnouncementVisibility::Members;
        $user = $this->createUser(isMember: false);

        $this->assertFalse($visibility->isVisibleTo($user));
    }

    public function test_members_is_visible_to_member(): void
    {
        $visibility = AnnouncementVisibility::Members;
        $user = $this->createUser(isMember: true);

        $this->assertTrue($visibility->isVisibleTo($user));
    }

    /**
     * @group requires-laravel
     */
    public function test_it_returns_label(): void
    {
        // Skip this test in unit tests - it requires Laravel's __() helper
        // which is only available in Feature tests
        $this->markTestSkipped('This test requires Laravel\'s __() helper, run as Feature test');
    }

    private function createUser(bool $isMember = false): object
    {
        $user = new stdClass();
        $user->id = 1;
        $user->is_member = $isMember;

        return $user;
    }
}
