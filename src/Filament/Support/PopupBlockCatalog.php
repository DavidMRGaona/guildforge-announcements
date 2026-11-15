<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Support;

use App\Application\SiteBlocks\Contracts\SiteBlockInterface;
use App\Application\SiteBlocks\Services\SiteBlockRegistryInterface;
use App\Application\SiteBlocks\Services\SiteBlockRendererInterface;
use App\Domain\SiteBlocks\SiteBlockPlacement;
use Filament\Schemas\Components\Component;
use Throwable;

/**
 * The blocks the form of a window can offer (announcements#70), worked out once per request:
 * Filament asks for the options and the settings of the chosen block many times, and `available()`
 * asks every module block whether it can be drawn.
 *
 * The definitions come from every valid popup block, available or not, so a window saved with a
 * block whose provider is down keeps it (and its settings) while someone edits it; only the
 * available ones can be added.
 */
final class PopupBlockCatalog
{
    /** @var array<string, SiteBlockInterface>|null */
    private ?array $definitions = null;

    /** @var list<string>|null */
    private ?array $available = null;

    /**
     * What the select offers: the blocks that can be added now, and the one the window already has.
     *
     * @return array<string, string>
     */
    public function options(?string $current): array
    {
        $options = [];

        foreach ($this->definitions() as $key => $block) {
            if (in_array($key, $this->available(), true) || $key === $current) {
                $options[$key] = $this->label($block);
            }
        }

        // A block nobody registers now (its module is off): shown by its key, never lost on saving
        if ($current !== null && $current !== '' && ! isset($options[$current])) {
            $options[$current] = $current;
        }

        return $options;
    }

    /**
     * The settings fields of a block, fresh components each time (one component cannot belong to two
     * places of a form). Empty when nobody registers the block or its schema throws.
     *
     * @return list<Component>
     */
    public function settingsSchema(?string $key): array
    {
        $block = $this->find($key);

        if (! $block instanceof SiteBlockInterface) {
            return [];
        }

        try {
            return array_values($block->settingsSchema());
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Whether the form can show and save the settings of this block; without it the stored ones are kept.
     */
    public function hasForm(?string $key): bool
    {
        $block = $this->find($key);

        if (! $block instanceof SiteBlockInterface) {
            return false;
        }

        try {
            $block->settingsSchema();
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    /**
     * The name shown for a block, also by the preview.
     */
    public function labelOf(?string $key): ?string
    {
        $block = $this->find($key);

        return $block instanceof SiteBlockInterface ? $this->label($block) : $key;
    }

    private function find(?string $key): ?SiteBlockInterface
    {
        return $key === null ? null : ($this->definitions()[$key] ?? null);
    }

    private function label(SiteBlockInterface $block): string
    {
        try {
            $label = $block->label();
        } catch (Throwable) {
            return $block->key();
        }

        return trim($label) === '' ? $block->key() : $label;
    }

    /**
     * @return array<string, SiteBlockInterface>
     */
    private function definitions(): array
    {
        if ($this->definitions === null) {
            $this->definitions = [];

            try {
                foreach (resolve(SiteBlockRegistryInterface::class)->forPlacement(SiteBlockPlacement::Popup) as $block) {
                    $this->definitions[$block->key()] = $block;
                }
            } catch (Throwable) {
                // A form without blocks is still a form
            }
        }

        return $this->definitions;
    }

    /**
     * @return list<string>
     */
    private function available(): array
    {
        if ($this->available === null) {
            try {
                $this->available = array_map(
                    static fn (SiteBlockInterface $block): string => $block->key(),
                    resolve(SiteBlockRendererInterface::class)->available(SiteBlockPlacement::Popup),
                );
            } catch (Throwable) {
                $this->available = [];
            }
        }

        return $this->available;
    }
}
