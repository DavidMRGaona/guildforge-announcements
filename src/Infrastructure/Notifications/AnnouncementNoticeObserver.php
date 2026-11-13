<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Notifications;

use App\Application\Mail\Services\MailSubjectMuteServiceInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Announcements\Infrastructure\Notifications\Jobs\AnnounceAnnouncementJob;
use Modules\Announcements\Infrastructure\Persistence\Eloquent\Models\AnnouncementModel;
use Modules\Announcements\Notifications\AnnouncementPublishedNotification;
use Throwable;

/**
 * Queues the bell notice of an announcement (announcements#63) when a save may have made it
 * shown: created active, turned on, or its start moved. A scheduled one is queued until its
 * start. Edits of text, colours or position say nothing, and the job tells it only once.
 *
 * Queued once the save commits, so a rolled back save tells nobody. The "created" and "updated"
 * events are used, not "saved": wasRecentlyCreated stays true for the instance's whole life.
 */
final readonly class AnnouncementNoticeObserver
{
    public function __construct(
        private MailSubjectMuteServiceInterface $subjects,
    ) {}

    public function created(AnnouncementModel $model): void
    {
        $this->queueWhenShown($model);
    }

    public function updated(AnnouncementModel $model): void
    {
        if ($model->wasChanged(['is_active', 'starts_at'])) {
            $this->queueWhenShown($model);
        }
    }

    private function queueWhenShown(AnnouncementModel $model): void
    {
        if ($model->is_active !== true) {
            return;
        }

        if ($model->ends_at instanceof CarbonInterface && $model->ends_at->isPast()) {
            return;
        }

        $id = $model->id;
        $startsAt = $model->starts_at instanceof CarbonInterface && $model->starts_at->isFuture() ? $model->starts_at->copy() : null;
        $excluded = is_string(Auth::id()) ? [Auth::id()] : [];

        DB::afterCommit(static function () use ($id, $startsAt, $excluded): void {
            try {
                AnnounceAnnouncementJob::dispatch($id, $excluded)->delay($startsAt);
            } catch (Throwable $exception) {
                // The announcement stands; only its notices are lost
                Log::warning('The notice of an announcement could not be queued.', ['announcement_id' => $id, 'exception' => $exception->getMessage()]);
            }
        });
    }

    public function deleted(AnnouncementModel $model): void
    {
        $id = $model->id;

        DB::afterCommit(function () use ($id): void {
            try {
                $this->subjects->forgetSubject(AnnouncementPublishedNotification::SUBJECT_TYPE, $id);
            } catch (Throwable $exception) {
                // The announcement is gone either way: only notices linking to the home page stay behind
                Log::warning('The notices of a deleted announcement could not be forgotten.', ['announcement_id' => $id, 'exception' => $exception->getMessage()]);
            }
        });
    }
}
