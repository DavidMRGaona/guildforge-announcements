import type { AnnouncementPopup } from '../types/announcement';

/**
 * What this browser remembers about the windows (announcements#68). For each window a time stamp
 * of when it was last shown and when it was closed, kept in `localStorage`; the windows opened in
 * this visit, kept in `sessionStorage` (a window opens at most once per visit, whatever its
 * frequency). The visit also counts the pages seen, for the «after N pages» trigger.
 *
 * Storage that is full or blocked never stops a page: what could not be written is kept in the
 * memory of the page instead, so a window still opens once per page load. While an administrator
 * views the site as someone else the memory is only that of the page: nothing of the
 * administrator's browser is read or written.
 */
const VIEWS_KEY = 'guildforge_popup_views';
const SHOWN_KEY = 'guildforge_shown_popups';
const PAGE_VIEWS_KEY = 'guildforge_popup_page_views';

const DAY_MS = 86_400_000;
/** The longest a window can wait is a year: older notes are of windows that no longer exist */
const KEEP_MS = 400 * DAY_MS;

/** When a window was last shown in this browser and when it was last closed (epoch milliseconds) */
export interface PopupRecord {
    shown: number | null;
    dismissed: number | null;
}

type Records = Record<string, PopupRecord>;

type PopupFrequencyRule = Pick<AnnouncementPopup, 'frequency' | 'frequency_days'>;

// What storage could not hold, and everything while the storage is left alone
let volatile = false;
const pageRecords = new Map<string, PopupRecord>();
const pageShown = new Set<string>();

/** Leaves the browser's storage alone (an impersonation) or goes back to using it */
export function setVolatileMemory(on: boolean): void {
    if (on !== volatile) {
        // What the page kept in one mode is not for the other: after an impersonation the
        // administrator's own windows must not stay hidden by what the person they looked as saw
        pageRecords.clear();
        pageShown.clear();
        keptPageViews = null;
    }

    volatile = on;
}

/** Forgets what the page kept in its own memory (tests) */
export function resetPageMemory(): void {
    volatile = false;
    pageRecords.clear();
    pageShown.clear();
    keptPageViews = null;
}

function storage(kind: 'local' | 'session'): Storage | null {
    try {
        return kind === 'local' ? window.localStorage : window.sessionStorage;
    } catch {
        return null;
    }
}

function timestamp(value: unknown): number | null {
    return typeof value === 'number' && Number.isFinite(value) ? value : null;
}

function readRecords(): Records {
    try {
        const parsed: unknown = JSON.parse(storage('local')?.getItem(VIEWS_KEY) ?? '{}');

        if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
            return {};
        }

        const records: Records = {};

        for (const [id, value] of Object.entries(parsed)) {
            if (typeof value === 'object' && value !== null) {
                const shown = 'shown' in value ? timestamp(value.shown) : null;
                const dismissed = 'dismissed' in value ? timestamp(value.dismissed) : null;

                if (shown !== null || dismissed !== null) {
                    records[id] = { shown, dismissed };
                }
            }
        }

        return records;
    } catch {
        return {};
    }
}

/** True when the records were written; false when the storage refused them */
function writeRecords(records: Records): boolean {
    try {
        const local = storage('local');

        if (local === null) {
            return false;
        }

        local.setItem(VIEWS_KEY, JSON.stringify(records));

        return true;
    } catch {
        return false;
    }
}

function latest(a: number | null, b: number | null): number | null {
    return a === null ? b : b === null ? a : Math.max(a, b);
}

/** What the browser remembers of one window: its storage and what the page kept besides it */
export function readRecord(id: string): PopupRecord {
    const kept = pageRecords.get(id);
    const stored = volatile ? undefined : readRecords()[id];

    return {
        shown: latest(stored?.shown ?? null, kept?.shown ?? null),
        dismissed: latest(stored?.dismissed ?? null, kept?.dismissed ?? null),
    };
}

/** Stores a window's record (null: no record), in the storage or, failing that, in the page */
function storeRecord(id: string, record: PopupRecord | null): void {
    pageRecords.delete(id);

    if (!volatile) {
        const records = readRecords();
        const cutoff = Date.now() - KEEP_MS;

        for (const [other, value] of Object.entries(records)) {
            if ((latest(value.shown, value.dismissed) ?? 0) < cutoff) {
                delete records[other];
            }
        }

        if (record === null) {
            delete records[id];
        } else {
            records[id] = record;
        }

        if (writeRecords(records)) {
            return;
        }
    }

    if (record !== null) {
        pageRecords.set(id, record);
    }
}

function readIds(): string[] {
    try {
        const parsed: unknown = JSON.parse(storage('session')?.getItem(SHOWN_KEY) ?? '[]');

        return Array.isArray(parsed)
            ? parsed.filter((id): id is string => typeof id === 'string')
            : [];
    } catch {
        return [];
    }
}

function writeIds(ids: string[]): boolean {
    try {
        const session = storage('session');

        if (session === null) {
            return false;
        }

        session.setItem(SHOWN_KEY, JSON.stringify(ids));

        return true;
    } catch {
        return false;
    }
}

/** Opened in this visit (this tab's session), or on this page load when the storage fails */
export function wasShownThisVisit(id: string): boolean {
    return pageShown.has(id) || (!volatile && readIds().includes(id));
}

function rememberVisit(id: string): void {
    if (!volatile) {
        const ids = readIds();

        if (ids.includes(id) || writeIds([...ids, id])) {
            return;
        }
    }

    pageShown.add(id);
}

function forgetVisit(id: string): void {
    pageShown.delete(id);

    if (!volatile) {
        writeIds(readIds().filter((shown) => shown !== id));
    }
}

/**
 * Whether this browser lets the window open now: not already opened in this visit, and, by its
 * frequency, never seen (once), or seen long enough ago (every N days). A time stamp from the
 * future (a clock that moved back) is not trusted, so it never hides a window for good.
 */
export function isDue(id: string, popup: PopupFrequencyRule, now: number = Date.now()): boolean {
    if (wasShownThisVisit(id)) {
        return false;
    }

    const record = readRecord(id);
    const seenAt = record.shown ?? record.dismissed;

    if (seenAt === null) {
        return true;
    }

    switch (popup.frequency) {
        case 'once':
            return false;
        case 'session':
            // The visit check above is the whole rule
            return true;
        case 'days':
            return seenAt > now || now >= seenAt + (popup.frequency_days ?? 1) * DAY_MS;
    }
}

/**
 * The window opened. Returns what the browser remembered before, for `forgetShown` when the
 * window has to be put away unseen.
 */
export function markShown(id: string, now: number = Date.now()): PopupRecord {
    const before = readRecord(id);

    storeRecord(id, { shown: now, dismissed: before.dismissed });
    rememberVisit(id);

    return before;
}

/** The window was closed (✕, Esc, backdrop or its button): it counts as seen */
export function markDismissed(id: string, now: number = Date.now()): void {
    const before = readRecord(id);

    storeRecord(id, { shown: before.shown ?? now, dismissed: now });
}

/** A window closed before the visitor saw it through (the cookie banner came back) may open again */
export function forgetShown(id: string, before: PopupRecord | null = null): void {
    forgetVisit(id);
    storeRecord(
        id,
        before !== null && (before.shown !== null || before.dismissed !== null) ? before : null
    );
}

interface PageViews {
    count: number;
    last: string | null;
}

/** null when the visit has no count in the storage (nothing yet, or the storage fails) */
function readPageViews(): PageViews | null {
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

    return null;
}

// The count of this page load, only when the storage could not keep it (or is left alone)
let keptPageViews: PageViews | null = null;

/**
 * Counts a page of this visit and returns how many there have been, this one included. Staying
 * on the same path (a reload, a filter or a page of results) is not another page.
 */
export function countPageView(pathname: string): number {
    const views = (volatile ? null : readPageViews()) ?? keptPageViews ?? { count: 0, last: null };

    if (views.last === pathname) {
        return views.count;
    }

    const next = { count: views.count + 1, last: pathname };
    let written = false;

    if (!volatile) {
        try {
            const session = storage('session');

            session?.setItem(PAGE_VIEWS_KEY, JSON.stringify(next));
            written = session !== null;
        } catch {
            // Counted for this page load only
        }
    }

    keptPageViews = written ? null : next;

    return next.count;
}
