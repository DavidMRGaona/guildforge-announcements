<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\DTOs;

final readonly class ContentImageMoveReportDTO
{
    /**
     * @param  int  $checked  Announcements read
     * @param  list<string>  $moved  "announcements#<id>: <local URL> → <Cloudinary path>" of every image moved (or that would move)
     * @param  list<string>  $broken  "announcements#<id>: <URL>" of local images that are missing or unsafe, left as they are
     * @param  list<string>  $failed  "announcements#<id>: <URL> (<reason>)" of images not moved, whose announcement and local copy are kept
     * @param  list<string>  $kept  "<local path> (<text>)" of local copies moved but kept, because another text still names them
     */
    public function __construct(
        public int $checked,
        public array $moved,
        public array $broken,
        public array $failed,
        public array $kept,
    ) {}
}
