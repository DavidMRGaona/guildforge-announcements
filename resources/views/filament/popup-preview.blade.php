
{{-- A picture of the window, not the window: the panel does not compile the site's styles, so it carries its own --}}
<style>
    .announcement-preview { padding: 1.25rem; border-radius: .75rem; background: var(--gray-100, #f4f4f5); }
    .dark .announcement-preview { background: var(--gray-950, #09090b); }
    .announcement-preview[data-shape="corner"] { display: flex; justify-content: flex-end; align-items: flex-end; min-height: 18rem; }
    .announcement-preview[data-shape="centered"] { display: flex; justify-content: center; align-items: center; background: rgb(0 0 0 / .45); }
    .announcement-preview__window { position: relative; width: 100%; max-width: 32rem; overflow: hidden; border-radius: .5rem; background: #fff; color: #18181b; box-shadow: 0 10px 25px rgb(0 0 0 / .25); }
    .announcement-preview[data-shape="corner"] .announcement-preview__window { max-width: 24rem; }
    .dark .announcement-preview__window { background: var(--gray-900, #18181b); color: #fafafa; }
    .announcement-preview__image { display: block; width: 100%; max-height: 14rem; object-fit: cover; }
    .announcement-preview__body { padding: 1.25rem; }
    .announcement-preview__title { margin: 0 2rem 0 0; font-size: 1.125rem; font-weight: 600; }
    .announcement-preview__content { margin-top: .5rem; font-size: .875rem; line-height: 1.5; }
    .announcement-preview__content p { margin: 0 0 .5rem; }
    .announcement-preview__content ul { list-style: disc; padding-left: 1.25rem; }
    .announcement-preview__content ol { list-style: decimal; padding-left: 1.25rem; }
    .announcement-preview__content a { text-decoration: underline; }
    .announcement-preview__button { display: inline-block; margin-top: 1rem; padding: .5rem 1rem; border-radius: .375rem; background: rgb(var(--primary-600, 79 70 229)); color: #fff; font-size: .875rem; font-weight: 500; text-decoration: none; }
    .announcement-preview__close { position: absolute; top: .5rem; right: .5rem; width: 1.75rem; height: 1.75rem; border-radius: 9999px; background: rgb(0 0 0 / .08); text-align: center; line-height: 1.75rem; }
</style>

<div class="announcement-preview" data-shape="{{ $shape }}">
    <div class="announcement-preview__window" role="img" aria-label="{{ __('announcements::announcements.preview.aria') }}">
        @if ($imageUrl !== null)
            <img class="announcement-preview__image" src="{{ $imageUrl }}" alt="{{ $imageAlt }}">
        @endif

        <div class="announcement-preview__body">
            <h3 class="announcement-preview__title">{{ $title }}</h3>
            {{-- Cleaned by the same sanitizer as the saved content (PopupPreview) --}}
            <div class="announcement-preview__content">{!! $contentHtml !!}</div>

            @if ($actionLabel !== null && $actionUrl !== null)
                {{-- Not a link to follow from here: the preview only shows where it would go --}}
                <a class="announcement-preview__button" href="{{ $actionUrl }}" target="_blank" rel="noopener noreferrer">{{ $actionLabel }}</a>
            @endif
        </div>

        <span class="announcement-preview__close" aria-hidden="true">✕</span>
    </div>
</div>
