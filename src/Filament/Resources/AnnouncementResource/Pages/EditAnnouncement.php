<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Concerns\HasKnowledgeGuideAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
use Modules\Announcements\Filament\Actions\PopupPreviewAction;
use Modules\Announcements\Filament\Resources\AnnouncementResource;
use Modules\Announcements\Filament\Resources\AnnouncementResource\Widgets\PopupMetricsWidget;
use Modules\Announcements\Filament\Support\PopupBlockFormData;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;

class EditAnnouncement extends EditRecord
{
    use HasKnowledgeGuideAction;

    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            PopupPreviewAction::make(),
            DeleteAction::make(),
            $this->knowledgeGuideAction('announcements.panel.manage-announcements'),
        ]);
    }

    /**
     * The counters of a window, above its form (announcements#69).
     *
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        $record = $this->getRecord();

        return $record instanceof AnnouncementModel && $record->format === AnnouncementFormat::Popup
            ? [PopupMetricsWidget::class]
            : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        return PopupBlockFormData::normalise($data, $record instanceof AnnouncementModel ? $record->popup_block : null);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
