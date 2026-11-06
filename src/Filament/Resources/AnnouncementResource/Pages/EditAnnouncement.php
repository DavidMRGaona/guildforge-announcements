<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Concerns\HasKnowledgeGuideAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Announcements\Filament\Resources\AnnouncementResource;

class EditAnnouncement extends EditRecord
{
    use HasKnowledgeGuideAction;

    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            DeleteAction::make(),
            $this->knowledgeGuideAction('announcements.panel.manage-announcements'),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
