<?php

declare(strict_types=1);

namespace Modules\Announcements\Tests\Unit\Policies;

use App\Application\Authorization\Services\AuthorizationServiceInterface;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Modules\Announcements\Policies\AnnouncementPolicy;
use Tests\TestCase;

/**
 * The panel has no page of its own for one announcement: whoever may see the list may see each
 * announcement in it, so the view ability asks for the list permission.
 */
final class AnnouncementPolicyTest extends TestCase
{
    public function test_viewing_one_announcement_needs_the_list_permission(): void
    {
        $user = new UserModel;
        $this->grant($user, ['announcements:announcements.view_any']);

        $this->assertTrue((new AnnouncementPolicy)->view($user, new AnnouncementModel));
    }

    public function test_the_retired_view_permission_alone_does_not_grant_viewing(): void
    {
        $user = new UserModel;
        $this->grant($user, ['announcements:announcements.view']);

        $this->assertFalse((new AnnouncementPolicy)->view($user, new AnnouncementModel));
    }

    /**
     * @param  list<string>  $granted
     */
    private function grant(UserModel $user, array $granted): void
    {
        $authorization = $this->createStub(AuthorizationServiceInterface::class);
        $authorization->method('can')->willReturnCallback(
            static fn (object $who, string $key): bool => $who === $user && in_array($key, $granted, true),
        );
        $this->app->instance(AuthorizationServiceInterface::class, $authorization);
    }
}
