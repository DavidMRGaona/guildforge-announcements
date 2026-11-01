<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Infrastructure;

use Inertia\Testing\AssertableInertia;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The rich editor's value can be tampered with, so the content is cleaned before any page or
 * JSON response gets it: AnnouncementCard renders it as HTML.
 */
final class AnnouncementContentHtmlTest extends ModuleTestCase
{
    // In the form the sanitizer writes inline styles, so it survives cleaning as is
    private const string FORMATTED = '<p style="text-align:center;">Hola <strong>socios</strong></p>';

    private const string TAMPERED = self::FORMATTED
        .'<script>alert(1)</script>'
        .'<img src="https://example.com/a.png" onerror="alert(2)">'
        .'<a href="javascript:alert(3)">enlace</a>'
        .'<iframe src="https://example.com"></iframe>';

    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    private AnnouncementModel $announcement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->announcement = AnnouncementModel::create([
            'title' => 'Public Announcement',
            'content' => self::TAMPERED,
            'visibility' => 'public',
            'priority' => 5,
            'is_active' => true,
        ]);
    }

    public function test_the_announcements_shared_with_every_page_carry_clean_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): AssertableInertia {
                /** @var array<int, array{content: string}> $announcements */
                $announcements = $page->toArray()['props']['announcements'];
                $this->assertCleanContent($announcements[0]['content']);

                return $page;
            });
    }

    public function test_the_announcement_list_returns_clean_content(): void
    {
        $content = $this->getJson('/anuncios')->assertOk()->json('data.0.content');

        $this->assertIsString($content);
        $this->assertCleanContent($content);
    }

    public function test_a_single_announcement_returns_clean_content(): void
    {
        $content = $this->getJson('/anuncios/'.$this->announcement->id)->assertOk()->json('data.content');

        $this->assertIsString($content);
        $this->assertCleanContent($content);
    }

    private function assertCleanContent(string $content): void
    {
        $this->assertStringContainsString(self::FORMATTED, $content);
        $this->assertStringNotContainsStringIgnoringCase('<script', $content);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $content);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $content);
        $this->assertStringNotContainsStringIgnoringCase('<iframe', $content);
    }
}
