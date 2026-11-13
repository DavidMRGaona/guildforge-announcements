<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Notifications;

use App\Application\Authorization\Services\AssociationMembershipServiceInterface;
use App\Application\Mail\Services\MailCategoryRegistryInterface;
use App\Application\SiteNotifications\DTOs\SiteNotificationItemDTO;
use App\Application\SiteNotifications\Services\SiteNotificationPreferenceServiceInterface;
use App\Application\SiteNotifications\Services\SiteNotificationServiceInterface;
use App\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Announcements\Infrastructure\Notifications\Jobs\AnnounceAnnouncementJob;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Modules\Announcements\Notifications\AnnouncementMailCategories;
use Tests\Support\Modules\ModuleTestCase;

/**
 * A new announcement becomes a notice in the bell of every account that may see it (announcements#63),
 * once, when it starts being shown, and grouped while unread.
 */
final class AnnouncementBellNoticesTest extends ModuleTestCase
{
    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('es');
    }

    public function test_the_category_is_optional_site_only_and_shown_to_everybody(): void
    {
        $category = $this->app->make(MailCategoryRegistryInterface::class)->find(AnnouncementMailCategories::NEW_ANNOUNCEMENTS);

        $this->assertNotNull($category);
        $this->assertSame('announcements.new-announcements', $category->key);
        $this->assertFalse($category->isMandatory());
        $this->assertTrue($category->site);
        $this->assertFalse($category->mail);
        $this->assertNull($category->audience);
        $this->assertSame('megaphone', $category->icon);
    }

    public function test_an_active_announcement_tells_every_other_active_account(): void
    {
        $publisher = UserModel::factory()->create();
        $reader = UserModel::factory()->create();
        $deactivated = UserModel::factory()->create();
        $deactivated->delete();
        $anonymized = UserModel::factory()->create(['anonymized_at' => now()]);

        $this->actingAs($publisher);
        $this->announcement(['title' => 'Cerrado por vacaciones', 'content' => '<p>Del 1 al <strong>15</strong> de agosto.</p>']);

        $items = $this->latest($reader);
        $this->assertCount(1, $items);
        $this->assertSame('Nuevo anuncio: Cerrado por vacaciones', $items[0]->title);
        $this->assertSame('/', $items[0]->url);
        $this->assertSame('Del 1 al 15 de agosto.', $items[0]->body);
        $this->assertSame('megaphone', $items[0]->icon);
        $this->assertSame(AnnouncementMailCategories::NEW_ANNOUNCEMENTS, $items[0]->category);
        $this->assertSame(0, $this->unread($publisher));
        $this->assertSame(0, $this->unread($deactivated));
        $this->assertSame(0, $this->unread($anonymized));
    }

    public function test_an_announcement_for_signed_in_people_reaches_every_account(): void
    {
        $reader = UserModel::factory()->create();

        $this->announcement(['visibility' => 'authenticated']);

        $this->assertSame(1, $this->unread($reader));
    }

    public function test_an_announcement_for_association_members_reaches_only_them(): void
    {
        $socio = UserModel::factory()->create();
        $role = RoleModel::firstOrCreate(
            ['name' => AssociationMembershipServiceInterface::DEFAULT_ROLE],
            ['display_name' => 'Socio'],
        );
        $socio->roles()->attach($role->id);
        $other = UserModel::factory()->create();

        $this->announcement(['visibility' => 'members']);

        $this->assertSame(1, $this->unread($socio));
        $this->assertSame(0, $this->unread($other));
    }

    public function test_an_inactive_or_expired_announcement_tells_nobody(): void
    {
        $reader = UserModel::factory()->create();

        $this->announcement(['is_active' => false]);
        $this->announcement(['starts_at' => now()->subDays(10), 'ends_at' => now()->subDay()]);

        $this->assertSame(0, $this->unread($reader));
    }

    public function test_an_announcement_is_told_once_however_often_it_is_saved_or_turned_on_again(): void
    {
        $reader = UserModel::factory()->create();
        $announcement = $this->announcement(['title' => 'Nuevo horario']);

        $announcement->update(['title' => 'Nuevo horario de tarde']);
        $announcement->update(['is_active' => false]);
        $announcement->update(['is_active' => true]);

        $items = $this->latest($reader);
        $this->assertCount(1, $items);
        $this->assertSame(1, $items[0]->count);
        $this->assertSame('Nuevo anuncio: Nuevo horario', $items[0]->title);
    }

    public function test_turning_on_an_announcement_created_inactive_tells_it(): void
    {
        $reader = UserModel::factory()->create();
        $announcement = $this->announcement(['is_active' => false]);

        $announcement->update(['is_active' => true]);

        $this->assertSame(1, $this->unread($reader));
    }

    public function test_a_scheduled_announcement_is_told_when_it_starts(): void
    {
        Queue::fake();
        $start = now()->addDays(2)->startOfMinute();

        $announcement = $this->announcement(['starts_at' => $start]);

        Queue::assertPushed(
            AnnounceAnnouncementJob::class,
            fn (AnnounceAnnouncementJob $job): bool => $job->announcementId === $announcement->id
                && $job->delay instanceof Carbon
                && $job->delay->equalTo($start),
        );
    }

    public function test_the_announcement_job_waits_while_the_announcement_has_not_started(): void
    {
        $reader = UserModel::factory()->create();
        $queue = Queue::getFacadeRoot();
        Queue::fake();
        $announcement = $this->announcement(['starts_at' => now()->addDays(2)]);
        Queue::swap($queue);

        // A job that runs early (the start was moved later) neither tells nor uses up the announcement
        (new AnnounceAnnouncementJob($announcement->id))->handle();
        $this->assertSame(0, $this->unread($reader));
        $this->assertDatabaseCount('announcements_notices', 0);

        // Its start comes: the entity reads the real clock, so the start is moved instead
        AnnouncementModel::query()->whereKey($announcement->id)->update(['starts_at' => now()->subMinute()]);
        (new AnnounceAnnouncementJob($announcement->id))->handle();
        (new AnnounceAnnouncementJob($announcement->id))->handle();

        $this->assertSame(1, $this->unread($reader));
    }

    public function test_moving_the_start_queues_the_announcement_again(): void
    {
        Queue::fake();
        $announcement = $this->announcement(['starts_at' => now()->addDays(2)]);
        $later = now()->addDays(5)->startOfMinute();

        $announcement->update(['starts_at' => $later]);
        $announcement->update(['title' => 'Otro título']);

        Queue::assertPushed(AnnounceAnnouncementJob::class, 2);
        Queue::assertPushed(
            AnnounceAnnouncementJob::class,
            fn (AnnounceAnnouncementJob $job): bool => $job->delay instanceof Carbon && $job->delay->equalTo($later),
        );
    }

    public function test_an_announcement_taken_down_before_delivery_shows_nothing(): void
    {
        $reader = UserModel::factory()->create();
        $queue = Queue::getFacadeRoot();
        Queue::fake();
        $announcement = $this->announcement();
        $announcement->update(['is_active' => false]);
        Queue::swap($queue);

        (new AnnounceAnnouncementJob($announcement->id))->handle();

        $this->assertSame(0, $this->unread($reader));
    }

    public function test_announcements_in_a_row_become_one_grouped_row(): void
    {
        $reader = UserModel::factory()->create();

        $this->announcement(['title' => 'Primero']);
        $this->announcement(['title' => 'Segundo']);
        $this->announcement(['title' => 'Tercero']);

        $items = $this->latest($reader);
        $this->assertCount(1, $items);
        $this->assertSame(3, $items[0]->count);
        $this->assertSame('3 anuncios nuevos', $items[0]->title);
    }

    public function test_deleting_the_announcement_forgets_its_notices(): void
    {
        $reader = UserModel::factory()->create();
        $announcement = $this->announcement();
        $this->assertSame(1, $this->unread($reader));

        $announcement->delete();

        $this->assertSame(0, $this->unread($reader));
        $this->assertDatabaseCount('announcements_notices', 0);
    }

    public function test_a_person_who_turned_the_category_off_gets_nothing(): void
    {
        $reader = UserModel::factory()->create();
        $this->app->make(SiteNotificationPreferenceServiceInterface::class)
            ->update($reader, [AnnouncementMailCategories::NEW_ANNOUNCEMENTS => false]);

        $this->announcement();

        $this->assertSame(0, $this->unread($reader));
    }

    public function test_the_notices_never_reach_a_faked_notification_facade(): void
    {
        Notification::fake();
        UserModel::factory()->create();

        $this->announcement();

        Notification::assertNothingSent();
    }

    public function test_a_rolled_back_save_tells_nobody(): void
    {
        $reader = UserModel::factory()->create();

        DB::beginTransaction();
        $this->announcement();
        DB::rollBack();

        $this->assertSame(0, $this->unread($reader));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function announcement(array $attributes = []): AnnouncementModel
    {
        return AnnouncementModel::create([
            'title' => 'Anuncio',
            'content' => '<p>Texto.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    /**
     * @return list<SiteNotificationItemDTO>
     */
    private function latest(UserModel $user): array
    {
        return $this->app->make(SiteNotificationServiceInterface::class)->latest($user, 10);
    }

    private function unread(UserModel $user): int
    {
        return $this->app->make(SiteNotificationServiceInterface::class)->unreadCount($user);
    }
}
