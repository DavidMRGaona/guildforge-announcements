<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Announcements\Domain\ValueObjects\PopupAction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PopupActionTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function acceptedUrls(): array
    {
        return [
            'https' => ['https://example.org/hazte-socio'],
            'https with query' => ['https://example.org/alta?origen=ventana#form'],
            'relative path' => ['/eventos'],
            'relative path with query' => ['/buscar?q=rol'],
            'home' => ['/'],
        ];
    }

    #[DataProvider('acceptedUrls')]
    public function test_it_accepts_https_links_and_relative_paths(string $url): void
    {
        $this->assertTrue(PopupAction::isValidUrl($url));
        $this->assertSame($url, (new PopupAction('Saber más', $url))->url);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function refusedUrls(): array
    {
        return [
            'plain http' => ['http://example.org'],
            'javascript' => ['javascript:alert(1)'],
            'data' => ['data:text/html,hola'],
            'protocol relative' => ['//example.org/path'],
            'backslash' => ['/\\example.org'],
            'no slash' => ['eventos'],
            'blank' => ['   '],
            'spaces' => ['/eventos de rol'],
            'https without host' => ['https:///path'],
            'mailto' => ['mailto:junta@example.org'],
            'too long' => ['/'.str_repeat('a', 2048)],
        ];
    }

    #[DataProvider('refusedUrls')]
    public function test_it_refuses_any_other_link(string $url): void
    {
        $this->assertFalse(PopupAction::isValidUrl($url));

        $this->expectException(InvalidArgumentException::class);
        new PopupAction('Saber más', $url);
    }

    public function test_the_label_is_trimmed_and_required(): void
    {
        $this->assertSame('Apúntate', (new PopupAction('  Apúntate ', '/eventos'))->label);

        $this->expectException(InvalidArgumentException::class);
        new PopupAction('  ', '/eventos');
    }

    public function test_the_label_has_a_maximum_length(): void
    {
        $this->assertSame(60, mb_strlen((new PopupAction(str_repeat('á', 60), '/'))->label));

        $this->expectException(InvalidArgumentException::class);
        new PopupAction(str_repeat('á', 61), '/');
    }
}
