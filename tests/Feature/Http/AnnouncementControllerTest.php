<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Announcements\AnnouncementsServiceProvider;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\TestCase;

final class AnnouncementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register the module's service provider for tests
        $this->app->register(AnnouncementsServiceProvider::class);

        // Create the announcements table if it doesn't exist
        if (! Schema::hasTable('announcements_announcements')) {
            Schema::create('announcements_announcements', function ($table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->text('content');
                $table->string('visibility')->default('public');
                $table->unsignedTinyInteger('priority')->default(5);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['is_active', 'starts_at', 'ends_at']);
            });
        }
    }

    public function test_it_returns_empty_list_when_no_announcements(): void
    {
        $response = $this->getJson('/api/announcements');

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

        $response = $this->getJson('/api/announcements');

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

        $response = $this->getJson('/api/announcements');

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

        $response = $this->getJson('/api/announcements');

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

        $response = $this->getJson('/api/announcements');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.title', 'High Priority')
            ->assertJsonPath('data.1.title', 'Medium Priority')
            ->assertJsonPath('data.2.title', 'Low Priority');
    }
}
