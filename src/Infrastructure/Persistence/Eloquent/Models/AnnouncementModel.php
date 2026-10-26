<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Eloquent\Concerns\StoresInstantsInStorageTimezone;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Announcements\Domain\Enums\AnnouncementPosition;
use Modules\Announcements\Domain\Enums\AnnouncementVisibility;

/**
 * @property string $id
 * @property string $title
 * @property string $content
 * @property AnnouncementVisibility $visibility
 * @property AnnouncementPosition $position
 * @property int $priority
 * @property string|null $background_color
 * @property string|null $text_color
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 * @property bool $is_dismissible
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class AnnouncementModel extends Model
{
    use HasUuids;
    use StoresInstantsInStorageTimezone;

    protected $table = 'announcements_announcements';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'title',
        'content',
        'visibility',
        'position',
        'priority',
        'background_color',
        'text_color',
        'starts_at',
        'ends_at',
        'is_active',
        'is_dismissible',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => AnnouncementVisibility::class,
            'position' => AnnouncementPosition::class,
            'priority' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'is_dismissible' => 'boolean',
        ];
    }
}
