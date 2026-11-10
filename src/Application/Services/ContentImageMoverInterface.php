<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\Services;

use Modules\Announcements\Application\DTOs\ContentImageMoveReportDTO;

/**
 * Moves the images that the announcements' editor stored on the local disk, before it used
 * Cloudinary, to "announcements/content/<year>/<month>".
 */
interface ContentImageMoverInterface
{
    /**
     * Uploads every local image referenced in the announcements' HTML, points the HTML at it and
     * deletes the local copy once every announcement using it is saved. Running it again moves
     * nothing new; with $dryRun nothing is uploaded, written or deleted.
     */
    public function move(bool $dryRun = false): ContentImageMoveReportDTO;
}
