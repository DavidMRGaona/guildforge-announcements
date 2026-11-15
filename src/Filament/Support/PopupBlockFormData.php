<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Support;

/**
 * What the form submits for the block of a window, ready to save (announcements#70).
 */
final class PopupBlockFormData
{
    /**
     * Nothing chosen is no block, and a block whose settings the form cannot show (its module is
     * off, or its fields cannot be built) keeps the ones stored, which the form never held.
     *
     * @param  array<string, mixed>  $data
     * @param  array{key?: string|null, settings?: array<string, mixed>}|null  $stored  What the window had
     * @return array<string, mixed>
     */
    public static function normalise(array $data, ?array $stored = null, ?PopupBlockCatalog $catalog = null): array
    {
        if (! array_key_exists('popup_block', $data)) {
            return $data;
        }

        $block = $data['popup_block'];
        $key = is_array($block) && is_string($block['key'] ?? null) ? trim($block['key']) : '';

        if ($key === '') {
            $data['popup_block'] = null;

            return $data;
        }

        $catalog ??= resolve(PopupBlockCatalog::class);
        $settings = is_array($block['settings'] ?? null) ? $block['settings'] : [];

        if (! $catalog->hasForm($key)) {
            $settings = ($stored['key'] ?? null) === $key ? ($stored['settings'] ?? []) : [];
        }

        $data['popup_block'] = ['key' => $key, 'settings' => $settings];

        return $data;
    }
}
