<?php

declare(strict_types=1);

namespace Modules\Announcements\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Eloquent\Concerns\DeletesCloudinaryImages;
use App\Infrastructure\Persistence\Eloquent\Concerns\StoresInstantsInStorageTimezone;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Announcements\Domain\Enums\AnnouncementFormat;
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
 * @property AnnouncementFormat $format
 * @property string $popup_shape
 * @property string|null $popup_image_public_id
 * @property string|null $popup_action_label
 * @property string|null $popup_action_url
 * @property int|null $popup_trigger_delay
 * @property int|null $popup_trigger_scroll
 * @property bool $popup_trigger_exit_intent
 * @property int|null $popup_trigger_page_views
 * @property string $popup_pages
 * @property list<string>|null $popup_paths
 * @property string $popup_device
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class AnnouncementModel extends Model
{
    use DeletesCloudinaryImages;
    use HasUuids;
    use StoresInstantsInStorageTimezone;

    protected $table = 'announcements_announcements';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The window image: released through the core when it is replaced or the announcement is
     * deleted, so a file chosen from the media library and used elsewhere stays.
     *
     * @var array<string>
     */
    protected array $cloudinaryImageFields = ['popup_image_public_id'];

    /**
     * New rows are banners with the default window, as the migration gives the existing ones.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'format' => 'banner',
        'popup_shape' => 'centered',
        'popup_trigger_exit_intent' => false,
        'popup_pages' => 'all',
        'popup_device' => 'all',
    ];

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
        'format',
        'popup_shape',
        'popup_image_public_id',
        'popup_action_label',
        'popup_action_url',
        'popup_trigger_delay',
        'popup_trigger_scroll',
        'popup_trigger_exit_intent',
        'popup_trigger_page_views',
        'popup_pages',
        'popup_paths',
        'popup_device',
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
            'format' => AnnouncementFormat::class,
            'popup_trigger_delay' => 'integer',
            'popup_trigger_scroll' => 'integer',
            'popup_trigger_exit_intent' => 'boolean',
            'popup_trigger_page_views' => 'integer',
            'popup_paths' => 'array',
        ];
    }
}
