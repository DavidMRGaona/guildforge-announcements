<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Integration\Infrastructure\Media;

use Modules\Announcements\Infrastructure\Media\ContentMediaFileIds;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\Modules\ModuleTestCase;

/**
 * Delivery URLs of this site in an announcement's HTML, as the core's RichTextMediaSource reads them.
 */
final class ContentMediaFileIdsTest extends ModuleTestCase
{
    /** Delivery URL base of this site (the test prefix is the default one, guildforge) */
    private const string BASE = 'https://res.cloudinary.com/demo/image/upload/';

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    /**
     * @return array<string, string>
     */
    private function in(mixed $text): array
    {
        return app(ContentMediaFileIds::class)->in($text);
    }

    public function test_an_image_src_counts_with_the_url_that_names_it(): void
    {
        $url = self::BASE.'guildforge/announcements/content/2026/11/a.jpg';

        $this->assertSame(['announcements/content/2026/11/a' => $url], $this->in('<p><img src="'.$url.'" alt=""></p>'));
    }

    public function test_a_url_with_transformation_and_version_counts(): void
    {
        $this->assertSame(
            ['articles/x'],
            array_keys($this->in('<p><a href="'.self::BASE.'c_fill,w_300,h_200/v1700000000/guildforge/articles/x.png">foto</a></p>')),
        );
    }

    public function test_a_bare_url_in_the_text_counts(): void
    {
        // Every form of the URL is tried: more ids only protect more files
        $this->assertArrayHasKey('events/cartel', $this->in('<p>El cartel está en '.self::BASE.'v12/guildforge/events/cartel.webp. ¡Compártelo!</p>'));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function bareUrlsWithoutExtension(): iterable
    {
        $url = self::BASE.'v12/guildforge/announcements/content/2026/11/0b6f2c4e-5d1a-4c3b-9e7f-1a2b3c4d5e6f';

        yield 'inside guillemets' => ['<p>Mira «'.$url.'» antes del sábado.</p>'];
        yield 'inside curly quotes' => ['<p>El cartel: “'.$url.'”.</p>'];
        yield 'before a non-breaking space' => ['<p>'.$url.'&nbsp;ahora mismo</p>'];
        yield 'before an ellipsis' => ['<p>Está en '.$url.'… o eso creo</p>'];
        yield 'at the end of a paragraph' => ['<p>Está en '.$url.'</p><p>Otro párrafo</p>'];
    }

    #[DataProvider('bareUrlsWithoutExtension')]
    public function test_a_bare_url_without_extension_ends_where_the_url_ends(string $html): void
    {
        $this->assertArrayHasKey('announcements/content/2026/11/0b6f2c4e-5d1a-4c3b-9e7f-1a2b3c4d5e6f', $this->in($html));
    }

    public function test_a_srcset_an_encoded_url_and_json_escaped_slashes_count(): void
    {
        $found = $this->in(
            '<img srcset="'.self::BASE.'w_400/guildforge/events/small.jpg 1x, '.self::BASE.'w_800/guildforge/events/big.jpg 2x">'
            .'<img src="'.self::BASE.'guildforge/events/con%20espacio.jpg">'
            .'<img src="'.str_replace('/', '\\/', self::BASE.'guildforge/events/json.jpg').'">'
            .'<img src="'.str_replace('/', '&#47;', self::BASE.'guildforge/events/entity.jpg').'">',
        );

        $this->assertEqualsCanonicalizing(
            ['events/small', 'events/big', 'events/con espacio', 'events/json', 'events/entity'],
            array_keys($found),
        );
    }

    public function test_a_url_of_another_prefix_or_host_does_not_count(): void
    {
        $this->assertSame([], $this->in('<img src="'.self::BASE.'guildforge-smoke/events/a.jpg"><img src="https://example.com/guildforge/events/a.jpg">'));
    }

    public function test_the_same_file_twice_is_found_once(): void
    {
        $found = $this->in('<img src="'.self::BASE.'guildforge/events/a.jpg"><a href="'.self::BASE.'v3/guildforge/events/a.jpg">ver</a>');

        $this->assertSame(['events/a' => self::BASE.'guildforge/events/a.jpg'], $found);
    }

    /**
     * @return iterable<string, array{0: mixed}>
     */
    public static function emptyTexts(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'not a string' => [42];
        yield 'no URL' => ['<p>Hola</p>'];
    }

    #[DataProvider('emptyTexts')]
    public function test_a_text_without_urls_uses_nothing(mixed $text): void
    {
        $this->assertSame([], $this->in($text));
    }

    public function test_a_text_the_pattern_cannot_read_fails_instead_of_looking_unused(): void
    {
        $ids = app(ContentMediaFileIds::class);
        $jit = ini_get('pcre.jit');
        $limit = ini_get('pcre.backtrack_limit');
        // Any match now fails with a PCRE error, as a huge text could
        ini_set('pcre.jit', '0');
        ini_set('pcre.backtrack_limit', '0');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('could not be read for media URLs');
            $ids->in('<img src="'.self::BASE.'guildforge/events/a.jpg">');
        } finally {
            ini_set('pcre.jit', (string) $jit);
            ini_set('pcre.backtrack_limit', (string) $limit);
        }
    }
}
