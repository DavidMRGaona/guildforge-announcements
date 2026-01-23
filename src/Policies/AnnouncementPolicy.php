<?php

declare(strict_types=1);

namespace Modules\Announcements\Policies;

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;

final class AnnouncementPolicy
{
    public function viewAny(UserModel $user): bool
    {
        return $user->canManageContent();
    }

    public function view(UserModel $user, AnnouncementModel $announcement): bool
    {
        return $user->canManageContent();
    }

    public function create(UserModel $user): bool
    {
        return $user->canManageContent();
    }

    public function update(UserModel $user, AnnouncementModel $announcement): bool
    {
        return $user->canManageContent();
    }

    public function delete(UserModel $user, AnnouncementModel $announcement): bool
    {
        return $user->isAdmin();
    }

    public function restore(UserModel $user, AnnouncementModel $announcement): bool
    {
        return $user->canManageContent();
    }

    public function forceDelete(UserModel $user, AnnouncementModel $announcement): bool
    {
        return $user->isAdmin();
    }
}
