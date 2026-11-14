/**
 * What this browser remembers about the windows. Closing one (✕, Esc, its button) hides it for
 * good in this browser, as with the banners; opening one keeps it from opening again in the same
 * visit. The visit also counts the pages seen, for the «after N pages» trigger. Storage that is
 * full or blocked never stops a page: a window then simply knows less.
 */
const DISMISSED_KEY = 'guildforge_dismissed_popups';
const SHOWN_KEY = 'guildforge_shown_popups';
const PAGE_VIEWS_KEY = 'guildforge_popup_page_views';

function storage(kind: 'local' | 'session'): Storage | null {
    try {
        return kind === 'local' ? window.localStorage : window.sessionStorage;
    } catch {
        return null;
    }
}

function readIds(kind: 'local' | 'session', key: string): string[] {
    try {
        const parsed: unknown = JSON.parse(storage(kind)?.getItem(key) ?? '[]');

        return Array.isArray(parsed)
            ? parsed.filter((id): id is string => typeof id === 'string')
            : [];
    } catch {
        return [];
    }
}

function writeIds(kind: 'local' | 'session', key: string, ids: string[]): void {
    try {
        storage(kind)?.setItem(key, JSON.stringify(ids));
    } catch {
        // Nothing to do: the window is just not remembered
    }
}

export function isDismissed(id: string): boolean {
    return readIds('local', DISMISSED_KEY).includes(id);
}

export function markDismissed(id: string): void {
    const ids = readIds('local', DISMISSED_KEY);

    if (!ids.includes(id)) {
        writeIds('local', DISMISSED_KEY, [...ids, id]);
    }
}

export function wasShownThisVisit(id: string): boolean {
    return readIds('session', SHOWN_KEY).includes(id);
}

export function markShown(id: string): void {
    const ids = readIds('session', SHOWN_KEY);

    if (!ids.includes(id)) {
        writeIds('session', SHOWN_KEY, [...ids, id]);
    }
}

/** A window closed before the visitor saw it through (the cookie banner came back) may open again */
export function forgetShown(id: string): void {
    writeIds(
        'session',
        SHOWN_KEY,
        readIds('session', SHOWN_KEY).filter((shown) => shown !== id)
    );
}

interface PageViews {
    count: number;
    last: string | null;
}

function readPageViews(): PageViews {
    try {
        const parsed: unknown = JSON.parse(storage('session')?.getItem(PAGE_VIEWS_KEY) ?? 'null');

        if (
            typeof parsed === 'object' &&
            parsed !== null &&
            'count' in parsed &&
            typeof parsed.count === 'number' &&
            'last' in parsed
        ) {
            return {
                count: parsed.count,
                last: typeof parsed.last === 'string' ? parsed.last : null,
            };
        }
    } catch {
        // Start counting again
    }

    return { count: 0, last: null };
}

/**
 * Counts a page of this visit and returns how many there have been, this one included. Staying
 * on the same path (a reload, a filter or a page of results) is not another page.
 */
export function countPageView(pathname: string): number {
    const views = readPageViews();

    if (views.last === pathname) {
        return views.count;
    }

    const next = { count: views.count + 1, last: pathname };

    try {
        storage('session')?.setItem(PAGE_VIEWS_KEY, JSON.stringify(next));
    } catch {
        // Counted for this page only
    }

    return next.count;
}
