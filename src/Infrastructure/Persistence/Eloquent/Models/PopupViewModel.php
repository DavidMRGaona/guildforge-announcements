<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Eloquent\Concerns\StoresInstantsInStorageTimezone;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * What one account has seen of one window (announcements#68).
 *
 * @property string $id
 * @property string $user_id
 * @property string $announcement_id
 * @property Carbon|null $last_shown_at
 * @property Carbon|null $dismissed_at
 */
final class PopupViewModel extends Model
{
    use HasUuids;
    use StoresInstantsInStorageTimezone;

    protected $table = 'announcements_popup_views';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'announcement_id',
        'last_shown_at',
        'dismissed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_shown_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }
}
