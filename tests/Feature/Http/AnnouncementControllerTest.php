<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Http;

use App\Application\Authorization\Services\AssociationMembershipServiceInterface;
use App\Application\Services\SettingsServiceInterface;
use App\Domain\Enums\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\ModuleTestCase;

final class AnnouncementControllerTest extends ModuleTestCase
{
    protected ?string $moduleName = 'announcements';
    protected bool $autoEnableModule = true;

    public function test_it_returns_empty_list_when_no_announcements(): void
    {
        $response = $this->getJson('/anuncios');

        $response->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_it_returns_active_public_announcements(): void
    {
        AnnouncementModel::create([
            'title' => 'Public Announcement',
            'content' => '<p>This is a public announcement.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ]);

        $response = $this->getJson('/anuncios');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Public Announcement');
    }

    public function test_it_does_not_return_inactive_announcements(): void
    {
        AnnouncementModel::create([
            'title' => 'Inactive Announcement',
            'content' => '<p>This is inactive.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => false,
        ]);

        $response = $this->getJson('/anuncios');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_it_filters_announcements_by_date_range(): void
    {
        // Active announcement within date range
        AnnouncementModel::create([
            'title' => 'Current Announcement',
            'content' => '<p>Current.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        // Active announcement that has ended
        AnnouncementModel::create([
            'title' => 'Ended Announcement',
            'content' => '<p>Ended.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subDay(),
        ]);

        // Active announcement that hasn't started
        AnnouncementModel::create([
            'title' => 'Future Announcement',
            'content' => '<p>Future.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addWeek(),
        ]);

        $response = $this->getJson('/anuncios');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Current Announcement');
    }

    public function test_it_orders_announcements_by_priority(): void
    {
        AnnouncementModel::create([
            'title' => 'Low Priority',
            'content' => '<p>Low.</p>',
            'visibility' => 'public',
            'priority' => 3,
            'is_active' => true,
        ]);

        AnnouncementModel::create([
            'title' => 'High Priority',
            'content' => '<p>High.</p>',
            'visibility' => 'public',
            'priority' => 8,
            'is_active' => true,
        ]);

        AnnouncementModel::create([
            'title' => 'Medium Priority',
            'content' => '<p>Medium.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ]);

        $response = $this->getJson('/anuncios');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.title', 'High Priority')
            ->assertJsonPath('data.1.title', 'Medium Priority')
            ->assertJsonPath('data.2.title', 'Low Priority');
    }

    public function test_members_announcements_are_returned_to_association_members(): void
    {
        $this->createAnnouncement(['title' => 'Members Announcement', 'visibility' => 'members']);

        $response = $this->actingAs($this->userWithRole(AssociationMembershipServiceInterface::DEFAULT_ROLE))
            ->getJson('/anuncios');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Members Announcement');
    }

    public function test_members_announcements_are_hidden_from_users_with_only_the_member_role(): void
    {
        $this->createAnnouncement(['visibility' => 'members']);

        $response = $this->actingAs($this->userWithRole(UserRole::Member->value))->getJson('/anuncios');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_members_announcements_are_hidden_from_users_without_any_role(): void
    {
        $this->createAnnouncement(['visibility' => 'members']);
        // The legacy users.role column says "member" for everyone; it must not count
        $user = UserModel::factory()->create(['role' => UserRole::Member]);

        $response = $this->actingAs($user)->getJson('/anuncios');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_members_announcements_follow_the_configured_association_member_role(): void
    {
        $this->createAnnouncement(['title' => 'Board Announcement', 'visibility' => 'members']);
        app(SettingsServiceInterface::class)->set(AssociationMembershipServiceInterface::SETTING_KEY, 'junta');

        $this->actingAs($this->userWithRole('junta'))
            ->getJson('/anuncios')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Board Announcement');

        $this->actingAs($this->userWithRole(AssociationMembershipServiceInterface::DEFAULT_ROLE))
            ->getJson('/anuncios')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_members_announcements_are_hidden_from_admins_without_the_association_member_role(): void
    {
        $this->createAnnouncement(['visibility' => 'members']);

        $response = $this->actingAs($this->userWithRole(UserRole::Admin->value))->getJson('/anuncios');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_members_announcements_are_hidden_from_guests(): void
    {
        $this->createAnnouncement(['visibility' => 'members']);

        $response = $this->getJson('/anuncios');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_show_returns_a_public_active_announcement(): void
    {
        $announcement = $this->createAnnouncement(['title' => 'Visible Announcement']);

        $response = $this->getJson('/anuncios/'.$announcement->id);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Visible Announcement');
    }

    public function test_show_returns_404_for_an_inactive_announcement(): void
    {
        $announcement = $this->createAnnouncement(['is_active' => false]);

        $this->getJson('/anuncios/'.$announcement->id)->assertNotFound();
    }

    public function test_show_returns_404_for_an_expired_announcement(): void
    {
        $announcement = $this->createAnnouncement([
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subDay(),
        ]);

        $this->getJson('/anuncios/'.$announcement->id)->assertNotFound();
    }

    public function test_show_returns_404_for_an_authenticated_announcement_to_a_guest(): void
    {
        $announcement = $this->createAnnouncement(['visibility' => 'authenticated']);

        $this->getJson('/anuncios/'.$announcement->id)->assertNotFound();
    }

    public function test_show_returns_404_for_a_members_announcement_to_a_guest(): void
    {
        $announcement = $this->createAnnouncement(['visibility' => 'members']);

        $this->getJson('/anuncios/'.$announcement->id)->assertNotFound();
    }

    public function test_show_returns_404_for_a_members_announcement_to_a_non_member(): void
    {
        $announcement = $this->createAnnouncement(['visibility' => 'members']);

        $this->actingAs(UserModel::factory()->create())
            ->getJson('/anuncios/'.$announcement->id)
            ->assertNotFound();
    }

    public function test_show_returns_404_for_a_members_announcement_to_a_user_with_only_the_member_role(): void
    {
        $announcement = $this->createAnnouncement(['visibility' => 'members']);

        $this->actingAs($this->userWithRole(UserRole::Member->value))
            ->getJson('/anuncios/'.$announcement->id)
            ->assertNotFound();
    }

    public function test_show_returns_a_members_announcement_to_an_association_member(): void
    {
        $announcement = $this->createAnnouncement(['title' => 'For members', 'visibility' => 'members']);

        $this->actingAs($this->userWithRole(AssociationMembershipServiceInterface::DEFAULT_ROLE))
            ->getJson('/anuncios/'.$announcement->id)
            ->assertOk()
            ->assertJsonPath('data.title', 'For members');
    }

    public function test_show_returns_404_for_an_unknown_announcement(): void
    {
        $this->getJson('/anuncios/9b2f4c1e-0000-4000-8000-000000000000')->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAnnouncement(array $attributes = []): AnnouncementModel
    {
        return AnnouncementModel::create(array_merge([
            'title' => 'Announcement',
            'content' => '<p>Content.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ], $attributes));
    }

    private function userWithRole(string $roleName): UserModel
    {
        $role = RoleModel::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );
        $user = UserModel::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }
}
