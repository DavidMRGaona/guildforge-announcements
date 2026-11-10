<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Infrastructure\Content;

use Modules\Announcements\Infrastructure\Content\ContentImageTags;
use PHPUnit\Framework\TestCase;

final class ContentImageTagsTest extends TestCase
{
    public function test_it_reads_every_tag_with_its_decoded_attributes(): void
    {
        $html = '<p><img src="/storage/a&amp;b.png" data-id=\'a.png\' alt="x > y"><img src=/storage/c.png><img alt="none"></p>';

        $this->assertSame([
            ['tag' => '<img src="/storage/a&amp;b.png" data-id=\'a.png\' alt="x > y">', 'src' => '/storage/a&b.png', 'id' => 'a.png'],
            ['tag' => '<img src=/storage/c.png>', 'src' => '/storage/c.png', 'id' => null],
            ['tag' => '<img alt="none">', 'src' => null, 'id' => null],
        ], (new ContentImageTags)->read($html));
    }

    public function test_it_rewrites_only_the_listed_tags_and_adds_a_missing_id(): void
    {
        $html = '<p>Hola</p><img src="/storage/a.png" data-id="a.png" alt="A"><img src="/storage/b.png" alt="B"/><img src="/keep.png">';

        $rewritten = (new ContentImageTags)->rewrite($html, [
            '<img src="/storage/a.png" data-id="a.png" alt="A">' => ['src' => 'https://cdn.test/a?x=1&y=2', 'id' => 'announcements/content/2026/11/a.png'],
            '<img src="/storage/b.png" alt="B"/>' => ['src' => 'https://cdn.test/b', 'id' => 'announcements/content/2026/11/b.png'],
        ]);

        $this->assertSame(
            '<p>Hola</p><img src="https://cdn.test/a?x=1&amp;y=2" data-id="announcements/content/2026/11/a.png" alt="A">'
            .'<img src="https://cdn.test/b" alt="B" data-id="announcements/content/2026/11/b.png"/><img src="/keep.png">',
            $rewritten,
        );
    }
}
