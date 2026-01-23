<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Resources\AnnouncementResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Announcements\Filament\Resources\AnnouncementResource;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
