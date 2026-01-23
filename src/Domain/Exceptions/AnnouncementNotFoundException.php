<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\Exceptions;

use DomainException;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;

final class AnnouncementNotFoundException extends DomainException
{
    public static function withId(AnnouncementId $id): self
    {
        return new self(
            sprintf('Announcement with ID "%s" was not found.', $id->value)
        );
    }
}
