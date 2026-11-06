<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Concerns\HasKnowledgeGuideAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Modules\Announcements\Filament\Resources\AnnouncementResource;

class CreateAnnouncement extends CreateRecord
{
    use HasKnowledgeGuideAction;

    protected static string $resource = AnnouncementResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return array_filter([
            $this->knowledgeGuideAction('announcements.panel.create-announcement'),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
