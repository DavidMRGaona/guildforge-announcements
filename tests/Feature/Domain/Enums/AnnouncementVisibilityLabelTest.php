<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Feature\Domain\Enums;

use Modules\Announcements\Domain\Enums\AnnouncementVisibility;
use Tests\Support\Modules\ModuleTestCase;

final class AnnouncementVisibilityLabelTest extends ModuleTestCase
{
    protected ?string $moduleName = 'announcements';

    protected bool $autoEnableModule = true;

    public function test_each_visibility_has_a_translated_label(): void
    {
        $this->assertSame('Público', AnnouncementVisibility::Public->label());

        foreach (AnnouncementVisibility::cases() as $visibility) {
            $this->assertNotSame('announcements::announcements.visibility.'.$visibility->value, $visibility->label());
        }
    }
}
