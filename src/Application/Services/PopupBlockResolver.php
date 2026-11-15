<?php

declare(strict_types=1);

namespace Modules\Announcements\Application\Services;

use App\Application\SiteBlocks\DTOs\ResolvedSiteBlockDTO;
use App\Application\SiteBlocks\Services\SiteBlockRendererInterface;
use App\Domain\SiteBlocks\SiteBlockPlacement;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Announcements\Domain\ValueObjects\PopupBlock;
use Throwable;

/**
 * Asks the core to resolve the block of a window for one person (announcements#70). The core
 * decides whether the block can be drawn now (its module on, its provider answering) and what its
 * component receives; this only fails closed: anything unexpected is no block.
 */
final readonly class PopupBlockResolver
{
    public function __construct(
        private SiteBlockRendererInterface $renderer,
    ) {}

    /**
     * @return array<string, mixed>|null Null when the block cannot be drawn now
     */
    public function resolve(PopupBlock $block, ?Authenticatable $user): ?array
    {
        try {
            $resolved = $this->renderer->resolveBlock($block->key, $block->settings, SiteBlockPlacement::Popup, $user);
        } catch (Throwable) {
            return null;
        }

        return $resolved instanceof ResolvedSiteBlockDTO ? $resolved->toArray() : null;
    }
}
