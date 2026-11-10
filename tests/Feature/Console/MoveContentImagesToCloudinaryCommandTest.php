<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Console;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Modules\Announcements\Application\DTOs\ContentImageMoveReportDTO;
use Modules\Announcements\Application\Services\ContentImageMoverInterface;
use Modules\Announcements\Console\Commands\MoveContentImagesToCloudinaryCommand;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\ModuleTestCase;

final class MoveContentImagesToCloudinaryCommandTest extends ModuleTestCase
{
    private const string COMMAND = 'announcements:move-editor-images-to-cloudinary';

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        // The module is booted after the console application exists: its command is added by hand
        $this->app->make(Kernel::class)->registerCommand($this->app->make(MoveContentImagesToCloudinaryCommand::class));
    }

    public function test_a_dry_run_lists_what_would_move(): void
    {
        $this->mover(true, new ContentImageMoveReportDTO(4, ['announcements#a1: /storage/x.png → announcements/content/2026/11/u.png'], ['announcements#a2: /storage/missing.png'], []));

        $this->artisanCommand(self::COMMAND, ['--dry-run' => true])
            ->expectsOutput('  announcements#a1: /storage/x.png → announcements/content/2026/11/u.png')
            ->expectsOutput('Broken references (left as they are):')
            ->expectsOutput('  announcements#a2: /storage/missing.png')
            ->expectsOutput('4 announcement(s) checked, 1 image(s) would move, 1 broken reference(s), 0 failure(s).')
            ->assertExitCode(0);
    }

    public function test_a_run_reports_what_moved(): void
    {
        $this->mover(false, new ContentImageMoveReportDTO(4, ['announcements#a1: /storage/x.png → announcements/content/2026/11/u.png'], [], []));

        $this->artisanCommand(self::COMMAND)
            ->expectsOutput('  announcements#a1: /storage/x.png → announcements/content/2026/11/u.png')
            ->doesntExpectOutput('Broken references (left as they are):')
            ->expectsOutput('4 announcement(s) checked, 1 image(s) moved, 0 broken reference(s), 0 failure(s).')
            ->assertExitCode(0);
    }

    public function test_a_failure_ends_with_an_error(): void
    {
        $this->mover(false, new ContentImageMoveReportDTO(4, [], [], ['announcements#a1: /storage/x.png (Upload refused)']));

        $this->artisanCommand(self::COMMAND)
            ->expectsOutput('Not moved (the announcement and its local copy are kept; run the command again):')
            ->expectsOutput('  announcements#a1: /storage/x.png (Upload refused)')
            ->expectsOutput('4 announcement(s) checked, 0 image(s) moved, 0 broken reference(s), 1 failure(s).')
            ->assertExitCode(1);
    }

    public function test_the_command_moves_the_images_and_a_dry_run_before_it_does_not(): void
    {
        Storage::fake('public')->put('a.png', 'png-bytes');
        $images = Storage::fake('images', ['url' => 'https://res.cloudinary.com/demo/image/upload/guildforge']);
        $announcement = AnnouncementModel::create([
            'title' => 'Cierre por vacaciones',
            'content' => '<p><img src="/storage/a.png"></p>',
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ]);

        $this->artisanCommand(self::COMMAND, ['--dry-run' => true])->assertExitCode(0);
        $this->assertSame([], Storage::disk('images')->allFiles());
        Storage::disk('public')->assertExists('a.png');

        $this->artisanCommand(self::COMMAND)->assertExitCode(0);
        $this->assertCount(1, $images->allFiles());
        Storage::disk('public')->assertMissing('a.png');
        $this->assertStringContainsString('/announcements/content/', (string) DB::table('announcements_announcements')->where('id', $announcement->id)->value('content'));

        $this->artisanCommand(self::COMMAND)
            ->expectsOutput('1 announcement(s) checked, 0 image(s) moved, 0 broken reference(s), 0 failure(s).')
            ->assertExitCode(0);
    }

    private function mover(bool $dryRun, ContentImageMoveReportDTO $report): void
    {
        $mover = Mockery::mock(ContentImageMoverInterface::class);
        $mover->shouldReceive('move')->once()->with($dryRun)->andReturn($report);
        $this->app->instance(ContentImageMoverInterface::class, $mover);
    }
}
