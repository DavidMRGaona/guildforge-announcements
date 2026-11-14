import type { PopupTriggers } from '../types/announcement';
import { MOBILE_SCREEN_QUERY } from './popupPages';

/** Delay that stands in for exit intent without a mouse, when the window has none (as on the server) */
export const EXIT_INTENT_FALLBACK_SECONDS = 10;

export interface PopupEnvironment {
    isMobile: boolean;
    /** A desktop screen with a mouse: the only place where leaving through the top can be seen */
    canDetectExitIntent: boolean;
    /** Pages seen in this visit, this one included */
    pageViews: number;
}

function matches(win: Window, query: string): boolean {
    try {
        return typeof win.matchMedia === 'function' && win.matchMedia(query).matches;
    } catch {
        return false;
    }
}

export function detectEnvironment(win: Window, pageViews: number): PopupEnvironment {
    const isMobile = matches(win, MOBILE_SCREEN_QUERY);

    return {
        isMobile,
        canDetectExitIntent: !isMobile && matches(win, '(hover: hover) and (pointer: fine)'),
        pageViews,
    };
}

/** How far down the page is scrolled, in %; null when the page cannot be scrolled */
function scrolledPercent(win: Window): number | null {
    const scrollable = win.document.documentElement.scrollHeight - win.innerHeight;

    return scrollable > 0 ? (win.scrollY / scrollable) * 100 : null;
}

/**
 * Waits for the first trigger of a window and calls `open` once. The triggers combine: whichever
 * is met first wins. Aborting the signal (navigation, unmount) removes every timer and listener.
 * A page that cannot be scrolled never meets the scroll trigger: only scrolling counts.
 */
export function armPopupTriggers(
    triggers: PopupTriggers,
    environment: PopupEnvironment,
    open: () => void,
    signal: AbortSignal,
    win: Window = window
): void {
    if (signal.aborted) {
        return;
    }

    const cleanups: Array<() => void> = [];
    let done = false;

    const stop = (): void => {
        done = true;
        cleanups.splice(0).forEach((cleanup) => cleanup());
    };

    const fire = (): void => {
        if (done || signal.aborted) {
            return;
        }

        stop();
        open();
    };

    signal.addEventListener('abort', stop, { once: true });
    cleanups.push(() => signal.removeEventListener('abort', stop));

    if (triggers.page_views !== null && environment.pageViews >= triggers.page_views) {
        fire();

        return;
    }

    let delay = triggers.delay_seconds;

    if (triggers.exit_intent && !environment.canDetectExitIntent && delay === null) {
        delay = EXIT_INTENT_FALLBACK_SECONDS;
    }

    if (delay !== null) {
        const timer = win.setTimeout(fire, delay * 1000);
        cleanups.push(() => win.clearTimeout(timer));
    }

    const percent = triggers.scroll_percent;

    if (percent !== null) {
        const onScroll = (): void => {
            const scrolled = scrolledPercent(win);

            if (scrolled !== null && scrolled >= percent) {
                fire();
            }
        };

        win.addEventListener('scroll', onScroll, { passive: true });
        cleanups.push(() => win.removeEventListener('scroll', onScroll));
    }

    if (triggers.exit_intent && environment.canDetectExitIntent) {
        const onMouseOut = (event: MouseEvent): void => {
            if (event.relatedTarget === null && event.clientY <= 0) {
                fire();
            }
        };

        win.document.addEventListener('mouseout', onMouseOut);
        cleanups.push(() => win.document.removeEventListener('mouseout', onMouseOut));
    }
}
