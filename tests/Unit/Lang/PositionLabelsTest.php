<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Lang;

use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PositionLabelsTest extends TestCase
{
    private const string MODULE_PATH = __DIR__.'/../../..';

    /**
     * @return array<string, array{string}>
     */
    public static function locales(): array
    {
        return ['es' => ['es'], 'en' => ['en']];
    }

    #[DataProvider('locales')]
    public function test_every_position_has_a_label_in_each_locale(string $locale): void
    {
        $labels = $this->positionLabels($locale);

        foreach (AnnouncementPosition::cases() as $position) {
            $key = str_replace('-', '_', $position->value);
            $this->assertArrayHasKey($key, $labels, "Missing {$locale} label for {$position->value}");
            $this->assertNotSame('', trim($labels[$key]));
        }
    }

    public function test_spanish_labels_do_not_use_english_layout_words(): void
    {
        foreach ($this->positionLabels('es') as $key => $label) {
            $this->assertDoesNotMatchRegularExpression('/header|footer/i', $label, "Label {$key} is not in Spanish");
        }
    }

    public function test_spanish_labels_match_the_readme(): void
    {
        $this->assertSame([
            'before_header' => 'Antes de la cabecera',
            'after_header' => 'Después de la cabecera',
            'before_content' => 'Antes del contenido',
            'after_content' => 'Después del contenido',
            'before_footer' => 'Antes del pie de página',
            'after_footer' => 'Después del pie de página',
        ], $this->positionLabels('es'));
    }

    /**
     * @return array<string, string>
     */
    private function positionLabels(string $locale): array
    {
        $translations = require self::MODULE_PATH."/lang/{$locale}/announcements.php";
        $this->assertIsArray($translations);
        $labels = $translations['position'] ?? [];
        $this->assertIsArray($labels);

        return $labels;
    }
}
