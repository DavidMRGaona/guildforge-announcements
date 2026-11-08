<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Filament;

use App\Filament\Actions\RowDetailsAction;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Livewire;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The announcements table overflowed its container by 468 px at 375 px and pushed the row
 * actions out of sight: its row actions sit in one menu and the secondary columns appear only
 * from the width where they fit.
 */
final class PanelTableFitsTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel([AnnouncementResource::class]);
        $this->actingAsPanelAdmin();
    }

    public function test_the_row_actions_sit_in_one_menu(): void
    {
        $actions = $this->table()->getRecordActions();

        $this->assertCount(1, $actions);
        $this->assertInstanceOf(ActionGroup::class, $actions[0]);
        $this->assertSame([RowDetailsAction::NAME, 'edit', 'delete'], array_keys($actions[0]->getFlatActions()));
        $this->assertNotNull($this->table()->getAction(RowDetailsAction::NAME));
    }

    public function test_the_priority_counter_is_centred(): void
    {
        $this->assertSame(Alignment::Center, $this->table()->getColumn('priority')?->getAlignment());
    }

    /**
     * @return array<string, array{string, ?string}>
     */
    public static function columnBreakpoints(): array
    {
        return [
            'title always shown' => ['title', null],
            'active always shown' => ['is_active', null],
            'priority' => ['priority', 'sm'],
            'visibility' => ['visibility', 'md'],
        ];
    }

    #[DataProvider('columnBreakpoints')]
    public function test_secondary_columns_appear_from_the_width_where_they_fit(string $column, ?string $breakpoint): void
    {
        $this->assertSame($breakpoint, $this->table()->getColumn($column)?->getVisibleFrom());
    }

    public function test_a_long_title_wraps_instead_of_widening_the_table(): void
    {
        $column = $this->table()->getColumn('title');

        $this->assertInstanceOf(TextColumn::class, $column);
        $this->assertTrue($column->canWrap());
    }

    private function table(): Table
    {
        $instance = Livewire::test(ListAnnouncements::class)->instance();
        $this->assertInstanceOf(HasTable::class, $instance);

        return $instance->getTable();
    }
}
