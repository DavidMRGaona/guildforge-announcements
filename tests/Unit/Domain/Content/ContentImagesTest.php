<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\Content;

use DateTimeImmutable;
use Modules\Announcements\Domain\Content\ContentImages;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContentImagesTest extends TestCase
{
    public function test_images_go_to_the_content_folder_of_their_month(): void
    {
        $this->assertSame('announcements/content/2026/03', ContentImages::directory(new DateTimeImmutable('2026-03-05 10:00:00')));
    }

    public function test_an_uploaded_image_path_is_recognised(): void
    {
        $this->assertTrue(ContentImages::isStoredPath('announcements/content/2026/11/0b7e7c1e-6a0f-4c56-9d0e-1b2f3a4c5d6e.png'));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function otherPaths(): iterable
    {
        yield 'a local upload' => ['3fJk2Lm9Qx.png'];
        yield 'another origin' => ['articles/content/2026/11/a.png'];
        yield 'outside the content folder' => ['announcements/2026/11/a.png'];
        yield 'a nested folder' => ['announcements/content/2026/11/x/a.png'];
        yield 'a delivery URL' => ['https://res.cloudinary.com/demo/image/upload/guildforge/announcements/content/2026/11/a.png'];
        yield 'empty' => [''];
    }

    #[DataProvider('otherPaths')]
    public function test_any_other_path_is_not_an_uploaded_image(string $path): void
    {
        $this->assertFalse(ContentImages::isStoredPath($path));
    }
}
