<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Providers;

use App\Application\Authorization\Services\AssociationMembershipServiceInterface;
use App\Domain\Enums\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

    public function test_windows_are_shared_apart_from_the_banners(): void
    {
        AnnouncementModel::create([
            'title' => 'Ventana pública',
            'content' => '<p>Ventana.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'format' => 'popup',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcements', 1)
                ->where('announcements.0.title', 'Public Announcement')
                ->has('announcementPopups', 1)
                ->where('announcementPopups.0.title', 'Ventana pública')
                ->where('announcementPopups.0.format', 'popup')
                ->where('announcementPopups.0.popup.shape', 'centered'));
    }

    public function test_a_window_for_visitors_without_a_session_is_not_shared_with_a_signed_in_user(): void
    {
        AnnouncementModel::create([
            'title' => 'Hazte socio',
            'content' => '<p>Únete.</p>',
            'visibility' => 'guests',
            'priority' => 5,
            'is_active' => true,
            'format' => 'popup',
        ]);

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('announcementPopups', 1));

        $this->actingAs($this->userWithRole(UserRole::Member->value))
            ->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('announcementPopups', 0));
    }

    public function test_windows_follow_the_audience_and_the_dates_of_the_announcement(): void
    {
        $this->popup('Para socios', ['visibility' => 'members']);
        $this->popup('Con sesión', ['visibility' => 'authenticated']);
        $this->popup('Inactiva', ['is_active' => false]);
        $this->popup('Caducada', ['starts_at' => now()->subWeek(), 'ends_at' => now()->subDay()]);
        $this->popup('Futura', ['starts_at' => now()->addDay()]);

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('announcementPopups', 0));

        $this->actingAs($this->userWithRole(UserRole::Member->value))
            ->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcementPopups', 1)
                ->where('announcementPopups.0.title', 'Con sesión'));

        $this->actingAs($this->userWithRole(AssociationMembershipServiceInterface::DEFAULT_ROLE))
            ->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcementPopups', 2)
                ->where('announcementPopups.0.title', 'Para socios')
                ->where('announcementPopups.1.title', 'Con sesión'));
    }

    public function test_banners_and_windows_are_read_with_one_query_per_request(): void
    {
        $this->popup('Ventana');
        $queries = 0;
        DB::listen(static function (QueryExecuted $query) use (&$queries): void {
            if (str_contains($query->sql, 'announcements_announcements')) {
                $queries++;
            }
        });

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcements', 1)
                ->has('announcementPopups', 1));

        $this->assertSame(1, $queries);
    }

    public function test_a_row_with_a_format_or_visibility_it_does_not_know_is_left_out_instead_of_breaking_the_page(): void
    {
        $log = Log::spy();
        foreach (['format' => 'carrusel', 'visibility' => 'vip'] as $column => $value) {
            DB::table('announcements_announcements')->insert([
                'id' => (string) Str::uuid(),
                'title' => 'Rota '.$column,
                'content' => '<p>x</p>',
                'is_active' => true,
                $column => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('announcements', 1)
                ->where('announcements.0.title', 'Public Announcement')
                ->has('announcementPopups', 0));

        $log->shouldHaveReceived('warning')->withArgs(
            static fn (string $message): bool => str_contains($message, 'cannot be read')
        )->atLeast()->once();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function popup(string $title, array $attributes = []): void
    {
        AnnouncementModel::create([
            'title' => $title,
            'content' => '<p>Ventana.</p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
            'format' => 'popup',
            ...$attributes,
        ]);
    }

    private function userWithRole(string $roleName): UserModel
    {
        $role = RoleModel::firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);
        $user = UserModel::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }
}
