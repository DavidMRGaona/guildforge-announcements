<?php

declare(strict_types=1);

namespace Modules\Announcements\Policies;

use App\Infrastructure\Authorization\Policies\AuthorizesWithPermissions;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;

final class AnnouncementPolicy
{
    use AuthorizesWithPermissions;

    public function viewAny(UserModel $user): bool
    {
        return $this->authorize($user, 'announcements:announcements.view_any');
    }

    public function view(UserModel $user, AnnouncementModel $announcement): bool
    {
        return $this->authorize($user, 'announcements:announcements.view');
    }

    public function create(UserModel $user): bool
    {
        return $this->authorize($user, 'announcements:announcements.create');
    }

    public function update(UserModel $user, AnnouncementModel $announcement): bool
    {
        return $this->authorize($user, 'announcements:announcements.update');
    }

    public function delete(UserModel $user, AnnouncementModel $announcement): bool
    {
        return $this->authorize($user, 'announcements:announcements.delete');
    }

    public function restore(UserModel $user, AnnouncementModel $announcement): bool
    {
        return $this->authorize($user, 'announcements:announcements.update');
    }

    public function forceDelete(UserModel $user, AnnouncementModel $announcement): bool
    {
        return $this->authorize($user, 'announcements:announcements.delete');
    }
}
