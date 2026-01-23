<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Resources\AnnouncementResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Announcements\Filament\Resources\AnnouncementResource;

class ListAnnouncements extends ListRecords
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
