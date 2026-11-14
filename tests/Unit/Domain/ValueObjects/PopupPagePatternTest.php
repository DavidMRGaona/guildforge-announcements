<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Domain\ValueObjects;

use Modules\Announcements\Domain\ValueObjects\PopupPagePattern;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PopupPagePatternTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function accepted(): array
    {
        return [
            'one page' => ['/eventos'],
            'nested page' => ['/eventos/torneo-de-otono'],
            'prefix' => ['/eventos*'],
            'prefix with slash' => ['/eventos/*'],
            'home' => ['/'],
            'everything' => ['/*'],
            'encoded' => ['/galer%C3%ADa'],
        ];
    }

    #[DataProvider('accepted')]
    public function test_it_accepts_paths_with_an_optional_final_star(string $pattern): void
    {
        $this->assertTrue(PopupPagePattern::isValid($pattern));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function refused(): array
    {
        return [
            'empty' => [''],
            'no slash' => ['eventos'],
            'star in the middle' => ['/eventos/*/fotos'],
            'two stars' => ['/eventos**'],
            'full url' => ['https://example.org/eventos'],
            'query' => ['/eventos?pagina=2'],
            'spaces' => ['/eventos de rol'],
            'protocol relative' => ['//example.org'],
            'too long' => ['/'.str_repeat('a', 255)],
        ];
    }

    #[DataProvider('refused')]
    public function test_it_refuses_anything_else(string $pattern): void
    {
        $this->assertFalse(PopupPagePattern::isValid($pattern));
    }
}
