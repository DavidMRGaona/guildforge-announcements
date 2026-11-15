<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Eloquent\Concerns\StoresInstantsInStorageTimezone;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * One anonymous counter of a window: a metric on a display day (announcements#69).
 *
 * @property string $id
 * @property string $announcement_id
 * @property string $day
 * @property string $metric
 * @property int $count
 */
final class PopupMetricModel extends Model
{
    use HasUuids;
    use StoresInstantsInStorageTimezone;

    protected $table = 'announcements_popup_metrics';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'announcement_id',
        'day',
        'metric',
        'count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // The day is a calendar date: kept as text so no zone moves it
            'day' => 'string',
            'count' => 'integer',
        ];
    }
}
