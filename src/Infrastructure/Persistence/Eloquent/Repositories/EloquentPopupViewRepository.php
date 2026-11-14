<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Modules\Announcements\Domain\Repositories\PopupViewRepositoryInterface;
use Modules\Announcements\Domain\ValueObjects\AnnouncementId;
use Modules\Announcements\Domain\ValueObjects\PopupView;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\PopupViewModel;

final class EloquentPopupViewRepository implements PopupViewRepositoryInterface
{
    /**
     * @param  list<string>  $announcementIds
     * @return array<string, PopupView>
     */
    public function forUser(string $userId, array $announcementIds): array
    {
        if ($announcementIds === []) {
            return [];
        }

        $views = [];

        $rows = PopupViewModel::query()
            ->where('user_id', $userId)
            ->whereIn('announcement_id', $announcementIds)
            ->get();

        foreach ($rows as $row) {
            $views[$row->announcement_id] = new PopupView(
                lastShownAt: $row->last_shown_at !== null ? DateTimeImmutable::createFromMutable($row->last_shown_at->toDateTime()) : null,
                dismissedAt: $row->dismissed_at !== null ? DateTimeImmutable::createFromMutable($row->dismissed_at->toDateTime()) : null,
            );
        }

        return $views;
    }

    public function recordShown(string $userId, AnnouncementId $announcementId, DateTimeImmutable $at): void
    {
        $this->write($userId, $announcementId, static function (PopupViewModel $view) use ($at): void {
            $view->last_shown_at = Carbon::instance($at);
        });
    }

    public function recordDismissed(string $userId, AnnouncementId $announcementId, DateTimeImmutable $at): void
    {
        $this->write($userId, $announcementId, static function (PopupViewModel $view) use ($at): void {
            $view->dismissed_at = Carbon::instance($at);
            // Closing a window means it was on the screen
            $view->last_shown_at ??= Carbon::instance($at);
        });
    }

    public function forgetUser(string $userId): void
    {
        PopupViewModel::query()->where('user_id', $userId)->delete();
    }

    /**
     * Two notices of the same account (a window opened and closed in two tabs) can reach the
     * server together: the one that loses the race on the unique index applies its change to
     * the row the other created.
     *
     * @param  callable(PopupViewModel): void  $change
     */
    private function write(string $userId, AnnouncementId $announcementId, callable $change): void
    {
        try {
            $this->apply($userId, $announcementId, $change);
        } catch (UniqueConstraintViolationException) {
            // The row exists now: the second try updates it
            $this->apply($userId, $announcementId, $change);
        }
    }

    /**
     * @param  callable(PopupViewModel): void  $change
     */
    private function apply(string $userId, AnnouncementId $announcementId, callable $change): void
    {
        $view = PopupViewModel::query()->firstOrNew([
            'user_id' => $userId,
            'announcement_id' => $announcementId->value,
        ]);

        $change($view);
        $view->save();
    }
}
