<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Content;

use App\Application\Media\Services\MediaIndexerInterface;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Modules\Announcements\Application\DTOs\ContentImageMoveReportDTO;
use Modules\Announcements\Application\Services\ContentImageMoverInterface;
use Modules\Announcements\Domain\Content\ContentImages;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Throwable;

/**
 * Before the editor used Cloudinary, images pasted in an announcement's text were stored on the
 * local "public" disk (Filament's default). Each one found in the stored HTML is uploaded to the
 * "images" disk, in "announcements/content/<year>/<month>", and the HTML is pointed at it there.
 * The same rules as the core's EditorImageMover (core#481), which only knows the core's columns:
 *
 * - Safe to run again: images already on Cloudinary are skipped, and a moved file is named after
 *   its content, so an upload repeated after an interrupted run overwrites the same asset.
 * - A local copy is deleted only once every announcement using it is saved; a failed upload or
 *   save leaves the announcement and its local copy as they were.
 * - Announcements are written straight to the table: moving an image is not an edit (no
 *   updated_at, no model events), and only if nobody saved the announcement meanwhile. Their
 *   media usages are refreshed right after, as no model event does it.
 */
final readonly class ContentImageMover implements ContentImageMoverInterface
{
    private const string LOCAL_DISK = 'public';

    private const string CLOUD_DISK = 'images';

    private const string TABLE = 'announcements_announcements';

    private const string COLUMN = 'content';

    /** Name space of the moved files' UUIDs (version 5): same content, same name */
    private const string NAMESPACE = 'c3a8e2f1-7b4d-5a9e-8c6f-2d1e0b9a7f43';

    public function __construct(
        private ContentImageTags $tags,
        private MediaIndexerInterface $indexer,
        private MimeTypeDetector $mimeTypes = new FinfoMimeTypeDetector,
    ) {}

    public function move(bool $dryRun = false): ContentImageMoveReportDTO
    {
        $announcements = $this->announcements();
        $moved = $broken = $failed = [];

        /** @var list<array{announcement: array{label: string, id: string, html: string}, images: list<array{tag: string, shown: string, path: string}>}> $plans */
        $plans = [];
        /** @var array<string, int> $references announcements using each local file */
        $references = [];

        foreach ($announcements as $announcement) {
            $images = [];

            foreach ($this->tags->read($announcement['html']) as $tag) {
                $path = $this->localPath($tag['src'], $tag['id']);

                if ($path === null) {
                    continue;
                }

                $shown = $tag['src'] ?? $tag['id'] ?? '';

                if (! $this->isMovable($path)) {
                    $broken[] = $announcement['label'].': '.$shown;

                    continue;
                }

                $images[] = ['tag' => $tag['tag'], 'shown' => $shown, 'path' => $path];
            }

            if ($images === []) {
                continue;
            }

            $plans[] = ['announcement' => $announcement, 'images' => $images];

            foreach (array_unique(array_column($images, 'path')) as $path) {
                $references[$path] = ($references[$path] ?? 0) + 1;
            }
        }

        /** @var array<string, string> $uploaded Cloudinary path of each local file already uploaded */
        $uploaded = [];
        /** @var array<string, int> $saved announcements saved with each local file moved */
        $saved = [];

        foreach ($plans as ['announcement' => $announcement, 'images' => $images]) {
            $rewrites = $lines = [];
            $current = null;

            try {
                foreach ($images as $image) {
                    $current = $image['shown'];
                    $target = $uploaded[$image['path']] ?? $this->upload($image['path'], $dryRun);
                    $uploaded[$image['path']] = $target;
                    $rewrites[$image['tag']] = ['src' => $this->cloud()->url($target), 'id' => $target];
                    $lines[] = $announcement['label'].': '.$image['shown'].' → '.$target;
                }

                $current = null;

                if (! $dryRun) {
                    $this->write($announcement, $this->tags->rewrite($announcement['html'], $rewrites));
                }
            } catch (Throwable $e) {
                $failed[] = $announcement['label'].($current !== null ? ': '.$current : '').' ('.$e->getMessage().')';

                continue;
            }

            if (! $dryRun) {
                // Outside the try: it never throws, and a saved announcement must count as saved
                $this->indexer->refresh(ContentImages::MEDIA_TYPE, $announcement['id']);
            }

            array_push($moved, ...$lines);

            foreach (array_unique(array_column($images, 'path')) as $path) {
                $saved[$path] = ($saved[$path] ?? 0) + 1;
            }
        }

        if (! $dryRun) {
            foreach ($saved as $path => $count) {
                if ($count === $references[$path] && ! $this->local()->delete($path)) {
                    $failed[] = $path.' (moved, but the local copy could not be deleted)';
                }
            }
        }

        return new ContentImageMoveReportDTO(count($announcements), $moved, $broken, $failed);
    }

    /**
     * @return list<array{label: string, id: string, html: string}>
     */
    private function announcements(): array
    {
        $announcements = [];
        $rows = DB::table(self::TABLE)->whereNotNull(self::COLUMN)->orderBy('id')->get(['id', self::COLUMN]);

        foreach ($rows as $row) {
            $announcements[] = [
                'label' => 'announcements#'.$row->id,
                'id' => (string) $row->id,
                'html' => (string) $row->{self::COLUMN},
            ];
        }

        return $announcements;
    }

    /**
     * The path on the local disk an image points at, or null when it is not a local image. Images
     * already in the content folder are on Cloudinary; the src wins over data-id, which Trix never wrote.
     */
    private function localPath(?string $src, ?string $id): ?string
    {
        if ($id !== null && ContentImages::isStoredPath($id)) {
            return null;
        }

        $fromSrc = $src !== null ? $this->pathOfLocalUrl($src) : null;

        if ($fromSrc !== null) {
            return $fromSrc;
        }

        if ($id === null || $id === '' || str_contains($id, '://')) {
            return null;
        }

        return $this->isMovable($id) ? $id : null;
    }

    /**
     * "/storage/<path>", relative or on the site's own host, as the local disk serves it.
     */
    private function pathOfLocalUrl(string $src): ?string
    {
        $base = (string) config('filesystems.disks.'.self::LOCAL_DISK.'.url');
        $prefix = rtrim((string) (parse_url($base, PHP_URL_PATH) ?? ''), '/').'/';
        $url = parse_url($src);

        if ($url === false || $prefix === '/' || isset($url['scheme']) && ! in_array(strtolower($url['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $hosts = array_filter([parse_url($base, PHP_URL_HOST), parse_url((string) config('app.url'), PHP_URL_HOST)]);
        $host = $url['host'] ?? null;

        if ($host !== null && ! in_array(strtolower($host), array_map(strtolower(...), $hosts), true)) {
            return null;
        }

        $path = $url['path'] ?? '';

        return str_starts_with($path, $prefix) ? rawurldecode(substr($path, strlen($prefix))) : null;
    }

    /**
     * An image file inside the local disk: no parent segments, nothing absolute.
     */
    private function isMovable(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '\\') || in_array('..', explode('/', $path), true)) {
            return false;
        }

        try {
            // By extension, as Cloudinary picks the resource type
            return str_starts_with((string) $this->mimeTypes->detectMimeTypeFromPath($path), 'image/') && $this->local()->exists($path);
        } catch (Throwable) {
            return false;
        }
    }

    private function upload(string $path, bool $dryRun): string
    {
        $contents = $this->local()->get($path) ?? throw new RuntimeException('Could not read the local copy');
        $month = CarbonImmutable::createFromTimestamp($this->local()->lastModified($path), (string) config('app.timezone'));
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $name = Uuid::uuid5(self::NAMESPACE, hash('sha256', $contents))->toString();
        $target = ContentImages::directory($month).'/'.$name.($extension !== '' ? '.'.$extension : '');

        if (! $dryRun && $this->cloud()->put($target, $contents) !== true) {
            throw new RuntimeException('Upload refused');
        }

        return $target;
    }

    /**
     * @param  array{id: string, html: string}  $announcement
     */
    private function write(array $announcement, string $html): void
    {
        $updated = DB::table(self::TABLE)
            ->where('id', $announcement['id'])
            ->where(self::COLUMN, $announcement['html'])
            ->update([self::COLUMN => $html]);

        if ($updated !== 1) {
            throw new RuntimeException('Changed while moving');
        }
    }

    private function local(): Filesystem
    {
        return Storage::disk(self::LOCAL_DISK);
    }

    /**
     * @return Filesystem&Cloud
     */
    private function cloud(): mixed
    {
        return Storage::disk(self::CLOUD_DISK);
    }
}
