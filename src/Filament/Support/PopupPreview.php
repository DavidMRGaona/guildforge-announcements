<?php

declare(strict_types=1);

namespace Modules\Announcements\Filament\Support;

use App\Infrastructure\Support\SanitizesHtml;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Announcements\Domain\Enums\PopupShape;
use Modules\Announcements\Domain\ValueObjects\PopupAction;
use Throwable;

/**
 * What the preview of a window draws (announcements#69), taken from the raw state of the form,
 * saved or not. It is checked as little as the form allows, so everything that reaches the page
 * is cleaned here: the content goes through the same sanitizer as the saved one, text is escaped
 * by the view, and a button only exists for a link the site would accept.
 */
final class PopupPreview
{
    use SanitizesHtml;

    /**
     * @param  array<string, mixed>  $state
     * @return array{title: string, contentHtml: string, shape: string, imageUrl: string|null, imageAlt: string, actionLabel: string|null, actionUrl: string|null, blockName: string|null, blockUnavailable: bool}
     */
    public function fromState(array $state): array
    {
        $shape = PopupShape::tryFrom($this->text($state['popup_shape'] ?? null) ?? '') ?? PopupShape::Centered;
        $label = $this->text($state['popup_action_label'] ?? null);
        $url = $this->text($state['popup_action_url'] ?? null);
        $hasButton = $label !== null && $url !== null && PopupAction::isValidUrl($url);

        return [
            'title' => $this->text($state['title'] ?? null) ?? '',
            'contentHtml' => $this->sanitizeHtml($this->html($state['content'] ?? null)),
            'shape' => $shape->value,
            'imageUrl' => $this->imageUrl($state['popup_image_public_id'] ?? null),
            'imageAlt' => $this->text($state['popup_image_alt'] ?? null) ?? '',
            'actionLabel' => $hasButton ? $label : null,
            'actionUrl' => $hasButton ? $url : null,
            'blockName' => $this->blockName($state['popup_block'] ?? null),
            'blockUnavailable' => $this->blockUnavailable($state),
        ];
    }

    /**
     * Only says where the block goes: it is never resolved nor run here (its provider may be a
     * mail service or a form that sends), so what it would show is never part of the preview.
     */
    private function blockName(mixed $block): ?string
    {
        $key = is_array($block) ? $this->text($block['key'] ?? null) : null;

        return $key === null ? null : resolve(PopupBlockCatalog::class)->labelOf(trim($key));
    }

    /**
     * A block that cannot be drawn now keeps the window from being shown, unless told otherwise.
     *
     * @param  array<string, mixed>  $state
     */
    private function blockUnavailable(array $state): bool
    {
        $block = $state['popup_block'] ?? null;
        $key = is_array($block) ? $this->text($block['key'] ?? null) : null;

        return $key !== null
            && ($state['popup_block_hide_when_unavailable'] ?? true) !== false
            && ! resolve(PopupBlockCatalog::class)->isAvailable(trim($key));
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }

    /**
     * The editor keeps its content as a document while it is edited and as HTML once saved.
     */
    private function html(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            try {
                return RichContentRenderer::make($content)->toHtml();
            } catch (Throwable) {
                return '';
            }
        }

        return '';
    }

    /**
     * A file just chosen is shown from its temporary copy; a saved one from the images disk.
     */
    private function imageUrl(mixed $state): ?string
    {
        $file = is_array($state) ? (reset($state) ?: null) : $state;

        try {
            if ($file instanceof UploadedFile && method_exists($file, 'temporaryUrl') && method_exists($file, 'isPreviewable') && $file->isPreviewable()) {
                $url = $file->temporaryUrl();

                return is_string($url) ? $url : null;
            }

            if (is_string($file) && $file !== '') {
                return Storage::disk('images')->url($file);
            }
        } catch (Throwable) {
            // A preview without its picture is still a preview
        }

        return null;
    }
}
