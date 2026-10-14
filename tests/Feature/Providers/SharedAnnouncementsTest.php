<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Providers;

use App\Application\Authorization\Services\AssociationMembershipServiceInterface;
use App\Domain\Enums\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Inertia\Testing\AssertableInertia;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The announcements shared with every Inertia page go through the same visibility rules as the API.
 */
final class SharedAnnouncementsTest extends ModuleTestCase
{
    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        AnnouncementModel::create([
            'title' => 'Public Announcement',
            'content' => '<p>Public.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ]);
        AnnouncementModel::create([
            'title' => 'Members Announcement',
            'content' => '<p>Members.</p>',
            'visibility' => 'members',
            'priority' => 8,
            'is_active' => true,
        ]);
    }

    public function test_a_user_with_only_the_member_role_does_not_receive_members_announcements(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Member->value))
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcements', 1)
                ->where('announcements.0.title', 'Public Announcement'));
    }

    public function test_an_association_member_receives_members_announcements(): void
    {
        $this->actingAs($this->userWithRole(AssociationMembershipServiceInterface::DEFAULT_ROLE))
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcements', 2)
                ->where('announcements.0.title', 'Members Announcement'));
    }

    private function userWithRole(string $roleName): UserModel
    {
        $role = RoleModel::firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);
        $user = UserModel::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }
}
