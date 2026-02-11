<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Http;

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
}
