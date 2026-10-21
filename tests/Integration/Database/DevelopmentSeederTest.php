<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Integration\Database;

use Modules\Announcements\Database\Seeders\DevelopmentSeeder;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\ModuleTestCase;

final class DevelopmentSeederTest extends ModuleTestCase
{
    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    private function runSeeder(): void
    {
        require_once dirname(__DIR__, 3).'/database/seeders/DevelopmentSeeder.php';

        (new DevelopmentSeeder)->setContainer($this->app)->run();
    }

    public function test_it_writes_nothing_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->assertTrue($this->app->environment('production'));

        $this->runSeeder();

        $this->assertSame(0, AnnouncementModel::count());
    }

    public function test_it_writes_nothing_in_the_testing_environment_so_suites_start_empty(): void
    {
        $this->runSeeder();

        $this->assertSame(0, AnnouncementModel::count());
    }

    public function test_it_is_idempotent(): void
    {
        $this->app['env'] = 'local';

        $this->runSeeder();
        $count = AnnouncementModel::count();

        $this->runSeeder();

        $this->assertGreaterThan(0, $count);
        $this->assertSame($count, AnnouncementModel::count());
    }

    public function test_it_seeds_every_priority_visibility_and_the_schedule_and_colour_cases(): void
    {
        $this->app['env'] = 'local';

        $this->runSeeder();

        $active = AnnouncementModel::where('is_active', true)->get();
        $this->assertTrue($active->contains(fn (AnnouncementModel $a): bool => $a->priority < 5 && $a->is_dismissible && $a->starts_at === null));
        $this->assertSame(1, AnnouncementModel::where('is_active', false)->whereBetween('priority', [5, 7])->where('visibility', AnnouncementVisibility::Public->value)->count());
        $this->assertSame(1, AnnouncementModel::where('is_active', false)->where('priority', '>=', 8)->count());
        $this->assertSame(1, AnnouncementModel::where('visibility', AnnouncementVisibility::Members->value)->count());
        $this->assertSame(1, AnnouncementModel::where('starts_at', '>', now())->count());
        $this->assertSame(1, AnnouncementModel::whereNotNull('background_color')->where('is_active', false)->count());
        $this->assertSame(1, AnnouncementModel::where('visibility', AnnouncementVisibility::Authenticated->value)->count());
    }
}
