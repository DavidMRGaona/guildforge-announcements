<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\Entities;

use DateTimeImmutable;
use Modules\Announcements\Domain\Entities\Announcement;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;
use Modules\Announcements\Domain\ValueObjects\AnnouncementPriority;
use PHPUnit\Framework\TestCase;
use stdClass;

final class AnnouncementTest extends TestCase
{
    public function test_it_creates_announcement_with_required_data(): void
    {
        $id = AnnouncementId::generate();
        $title = 'Important Announcement';
        $content = '<p>This is an important announcement.</p>';
        $visibility = AnnouncementVisibility::Public;
        $priority = new AnnouncementPriority(5);

        $announcement = new Announcement(
            id: $id,
            title: $title,
            content: $content,
            visibility: $visibility,
            position: AnnouncementPosition::BeforeHeader,
            priority: $priority,
        );

        $this->assertEquals($id, $announcement->id());
        $this->assertEquals($title, $announcement->title());
        $this->assertEquals($content, $announcement->content());
        $this->assertEquals($visibility, $announcement->visibility());
        $this->assertEquals(AnnouncementPosition::BeforeHeader, $announcement->position());
        $this->assertEquals($priority, $announcement->priority());
        $this->assertTrue($announcement->isActive());
        $this->assertNull($announcement->startsAt());
        $this->assertNull($announcement->endsAt());
    }

    public function test_it_creates_announcement_with_all_data(): void
    {
        $id = AnnouncementId::generate();
        $title = 'Scheduled Announcement';
        $content = '<p>This announcement has a schedule.</p>';
        $visibility = AnnouncementVisibility::Members;
        $priority = new AnnouncementPriority(10);
        $startsAt = new DateTimeImmutable('2025-01-01 00:00:00');
        $endsAt = new DateTimeImmutable('2025-01-31 23:59:59');
        $isActive = true;
        $createdAt = new DateTimeImmutable('2024-12-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2024-12-15 15:30:00');

        $position = AnnouncementPosition::AfterHeader;

        $announcement = new Announcement(
            id: $id,
            title: $title,
            content: $content,
            visibility: $visibility,
            position: $position,
            priority: $priority,
            startsAt: $startsAt,
            endsAt: $endsAt,
            isActive: $isActive,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        $this->assertEquals($id, $announcement->id());
        $this->assertEquals($title, $announcement->title());
        $this->assertEquals($content, $announcement->content());
        $this->assertEquals($visibility, $announcement->visibility());
        $this->assertEquals($position, $announcement->position());
        $this->assertEquals($priority, $announcement->priority());
        $this->assertEquals($startsAt, $announcement->startsAt());
        $this->assertEquals($endsAt, $announcement->endsAt());
        $this->assertTrue($announcement->isActive());
        $this->assertEquals($createdAt, $announcement->createdAt());
        $this->assertEquals($updatedAt, $announcement->updatedAt());
    }

    public function test_it_is_active_by_default(): void
    {
        $announcement = $this->createAnnouncement();

        $this->assertTrue($announcement->isActive());
    }

    public function test_it_activates(): void
    {
        $announcement = $this->createAnnouncement(isActive: false);

        $announcement->activate();

        $this->assertTrue($announcement->isActive());
    }

    public function test_it_deactivates(): void
    {
        $announcement = $this->createAnnouncement(isActive: true);

        $announcement->deactivate();

        $this->assertFalse($announcement->isActive());
    }

    public function test_it_checks_if_currently_valid_without_dates(): void
    {
        $announcement = $this->createAnnouncement(
            isActive: true,
            startsAt: null,
            endsAt: null,
        );

        $this->assertTrue($announcement->isCurrentlyValid());
    }

    public function test_it_is_not_valid_when_inactive(): void
    {
        $announcement = $this->createAnnouncement(isActive: false);

        $this->assertFalse($announcement->isCurrentlyValid());
    }

    public function test_it_is_valid_when_within_date_range(): void
    {
        $now = new DateTimeImmutable;
        $announcement = $this->createAnnouncement(
            isActive: true,
            startsAt: $now->modify('-1 day'),
            endsAt: $now->modify('+1 day'),
        );

        $this->assertTrue($announcement->isCurrentlyValid());
    }

    public function test_it_is_not_valid_when_before_start_date(): void
    {
        $now = new DateTimeImmutable;
        $announcement = $this->createAnnouncement(
            isActive: true,
            startsAt: $now->modify('+1 day'),
            endsAt: $now->modify('+7 days'),
        );

        $this->assertFalse($announcement->isCurrentlyValid());
    }

    public function test_it_is_not_valid_when_after_end_date(): void
    {
        $now = new DateTimeImmutable;
        $announcement = $this->createAnnouncement(
            isActive: true,
            startsAt: $now->modify('-7 days'),
            endsAt: $now->modify('-1 day'),
        );

        $this->assertFalse($announcement->isCurrentlyValid());
    }

    public function test_it_is_valid_when_only_start_date_set_and_passed(): void
    {
        $now = new DateTimeImmutable;
        $announcement = $this->createAnnouncement(
            isActive: true,
            startsAt: $now->modify('-1 day'),
            endsAt: null,
        );

        $this->assertTrue($announcement->isCurrentlyValid());
    }

    public function test_it_is_valid_when_only_end_date_set_and_not_passed(): void
    {
        $now = new DateTimeImmutable;
        $announcement = $this->createAnnouncement(
            isActive: true,
            startsAt: null,
            endsAt: $now->modify('+1 day'),
        );

        $this->assertTrue($announcement->isCurrentlyValid());
    }

    public function test_it_checks_visibility_for_guest(): void
    {
        $publicAnnouncement = $this->createAnnouncement(
            visibility: AnnouncementVisibility::Public,
        );
        $authenticatedAnnouncement = $this->createAnnouncement(
            visibility: AnnouncementVisibility::Authenticated,
        );
        $membersAnnouncement = $this->createAnnouncement(
            visibility: AnnouncementVisibility::Members,
        );

        $this->assertTrue($publicAnnouncement->isVisibleTo(null));
        $this->assertFalse($authenticatedAnnouncement->isVisibleTo(null));
        $this->assertFalse($membersAnnouncement->isVisibleTo(null));
    }

    public function test_it_checks_visibility_for_authenticated_user(): void
    {
        $user = $this->createUser(isMember: false);

        $publicAnnouncement = $this->createAnnouncement(
            visibility: AnnouncementVisibility::Public,
        );
        $authenticatedAnnouncement = $this->createAnnouncement(
            visibility: AnnouncementVisibility::Authenticated,
        );
        $membersAnnouncement = $this->createAnnouncement(
            visibility: AnnouncementVisibility::Members,
        );

        $this->assertTrue($publicAnnouncement->isVisibleTo($user));
        $this->assertTrue($authenticatedAnnouncement->isVisibleTo($user));
        $this->assertFalse($membersAnnouncement->isVisibleTo($user));
    }

    public function test_it_checks_visibility_for_member(): void
    {
        $member = $this->createUser(isMember: true);

        $publicAnnouncement = $this->createAnnouncement(
            visibility: AnnouncementVisibility::Public,
        );
        $authenticatedAnnouncement = $this->createAnnouncement(
            visibility: AnnouncementVisibility::Authenticated,
        );
        $membersAnnouncement = $this->createAnnouncement(
            visibility: AnnouncementVisibility::Members,
        );

        $this->assertTrue($publicAnnouncement->isVisibleTo($member));
        $this->assertTrue($authenticatedAnnouncement->isVisibleTo($member));
        $this->assertTrue($membersAnnouncement->isVisibleTo($member));
    }

    public function test_it_updates_announcement(): void
    {
        $announcement = $this->createAnnouncement();
        $newTitle = 'Updated Title';
        $newContent = '<p>Updated content.</p>';
        $newVisibility = AnnouncementVisibility::Members;
        $newPriority = new AnnouncementPriority(10);
        $newStartsAt = new DateTimeImmutable('+1 day');
        $newEndsAt = new DateTimeImmutable('+7 days');

        $newPosition = AnnouncementPosition::AfterContent;

        $announcement->update(
            title: $newTitle,
            content: $newContent,
            visibility: $newVisibility,
            position: $newPosition,
            priority: $newPriority,
            startsAt: $newStartsAt,
            endsAt: $newEndsAt,
        );

        $this->assertEquals($newTitle, $announcement->title());
        $this->assertEquals($newContent, $announcement->content());
        $this->assertEquals($newVisibility, $announcement->visibility());
        $this->assertEquals($newPosition, $announcement->position());
        $this->assertEquals($newPriority, $announcement->priority());
        $this->assertEquals($newStartsAt, $announcement->startsAt());
        $this->assertEquals($newEndsAt, $announcement->endsAt());
    }

    private function createAnnouncement(
        ?AnnouncementVisibility $visibility = null,
        ?AnnouncementPosition $position = null,
        bool $isActive = true,
        ?DateTimeImmutable $startsAt = null,
        ?DateTimeImmutable $endsAt = null,
    ): Announcement {
        return new Announcement(
            id: AnnouncementId::generate(),
            title: 'Test Announcement',
            content: '<p>Test content.</p>',
            visibility: $visibility ?? AnnouncementVisibility::Public,
            position: $position ?? AnnouncementPosition::BeforeHeader,
            priority: AnnouncementPriority::default(),
            startsAt: $startsAt,
            endsAt: $endsAt,
            isActive: $isActive,
        );
    }

    private function createUser(bool $isMember = false): object
    {
        $user = new stdClass;
        $user->id = 1;
        $user->is_member = $isMember;

        return $user;
    }
}
