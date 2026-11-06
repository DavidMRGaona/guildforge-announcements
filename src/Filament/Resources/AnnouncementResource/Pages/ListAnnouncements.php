<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Concerns\HasKnowledgeGuideAction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Announcements\Filament\Resources\AnnouncementResource;

class ListAnnouncements extends ListRecords
{
    use HasKnowledgeGuideAction;

    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            CreateAction::make(),
            $this->knowledgeGuideAction('announcements.panel.overview'),
        ]);
    }
}
