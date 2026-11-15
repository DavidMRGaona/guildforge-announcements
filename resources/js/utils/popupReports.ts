import { csrfHeaders } from '@/utils/csrf';

/** What the browser counts, anonymously, about a window (announcements#69) */
export type PopupMetric = 'shown' | 'dismissed' | 'action_clicked';

/** What the browser tells the server about a window of a signed-in account (announcements#68) */
export type PopupNotice = 'shown' | 'dismissed';

const PATHS: Record<PopupNotice, string> = {
    shown: 'mostrada',
    dismissed: 'cerrada',
};

/**
 * Tells the server a window was shown or closed, so this account does not get it again on
 * another device. Only the id travels. The page does nothing with the answer, and a failure
 * (offline, a throttled or refused notice) only means the account remembers less: the browser
 * keeps its own note anyway.
 */
export function reportPopup(id: string, notice: PopupNotice): void {
    try {
        void fetch(`/anuncios/ventanas/${encodeURIComponent(id)}/${PATHS[notice]}`, {
            method: 'POST',
            credentials: 'same-origin',
            // Closing often comes with leaving the page
            keepalive: true,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...csrfHeaders(),
            },
        }).catch(() => undefined);
    } catch {
        // Nothing to do: the account is just not told
    }
}

/**
 * Counts what happened to a window: shown, closed, or its button pressed (announcements#69). Open
 * to visitors without a session, and nothing but the id and the kind of event travels: the server
 * keeps a number per day and window, never who. Like the notices above, a failure only means one
 * less count.
 */
export function countPopup(id: string, metric: PopupMetric): void {
    try {
        void fetch(`/anuncios/ventanas/${encodeURIComponent(id)}/metrica/${metric}`, {
            method: 'POST',
            credentials: 'same-origin',
            // Pressing the button leaves the page
            keepalive: true,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...csrfHeaders(),
            },
        }).catch(() => undefined);
    } catch {
        // Nothing to do: the count is just lost
    }
}
