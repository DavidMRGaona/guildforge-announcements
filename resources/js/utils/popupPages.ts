import type { AnnouncementPopup, PopupDevice } from '../types/announcement';

/** The page a window may open on: its path and the Inertia component that draws it */
export interface PopupPageContext {
    pathname: string;
    component: string;
}

/**
 * Pages where a window never opens, whatever it says: the panel, signing in and its steps,
 * legal pages, maintenance, the newsletter page, error pages and the mail links (unsubscribe,
 * mute a subject), which people open to do one thing.
 */
const EXCLUDED_PATH_PREFIXES = ['/admin'];
const EXCLUDED_PATHS_AND_BELOW = ['/newsletter'];
const EXCLUDED_COMPONENT_PREFIXES = ['Auth/', 'Legal/', 'Mail/', 'Error/', 'Errors/'];
const EXCLUDED_COMPONENTS = ['Maintenance', 'Error'];

/** Phones are screens under 768 px wide, as in the panel help */
export const MOBILE_SCREEN_QUERY = '(max-width: 767px)';

/** The path of an Inertia page URL ("/eventos?pagina=2" → "/eventos"), without a final slash */
export function pathOf(url: string): string {
    let pathname: string;

    try {
        pathname = new URL(url, 'http://localhost').pathname;
    } catch {
        pathname = url.split(/[?#]/)[0] ?? '/';
    }

    if (pathname.length > 1 && pathname.endsWith('/')) {
        pathname = pathname.replace(/\/+$/, '');
    }

    return pathname === '' ? '/' : pathname;
}

export function isExcludedPage(context: PopupPageContext): boolean {
    const { pathname, component } = context;

    if (EXCLUDED_PATH_PREFIXES.some((prefix) => pathname.startsWith(prefix))) {
        return true;
    }

    if (
        EXCLUDED_PATHS_AND_BELOW.some(
            (path) => pathname === path || pathname.startsWith(`${path}/`)
        )
    ) {
        return true;
    }

    return (
        EXCLUDED_COMPONENTS.includes(component) ||
        EXCLUDED_COMPONENT_PREFIXES.some((prefix) => component.startsWith(prefix))
    );
}

/** "/eventos" is that page only; "/eventos*" is every path that starts with "/eventos" */
export function matchesPathPattern(pattern: string, pathname: string): boolean {
    if (pattern.endsWith('*')) {
        return pathname.startsWith(pattern.slice(0, -1));
    }

    return pathOf(pattern) === pathname;
}

/** Whether the window may open on this page (the pages it always skips aside) */
export function fitsPage(popup: AnnouncementPopup, context: PopupPageContext): boolean {
    if (isExcludedPage(context)) {
        return false;
    }

    switch (popup.pages) {
        case 'all':
            return true;
        case 'home':
            return context.pathname === '/';
        case 'paths':
            return popup.paths.some((pattern) => matchesPathPattern(pattern, context.pathname));
        default:
            // A value this version does not know: better no window than one in the wrong place
            return false;
    }
}

export function fitsDevice(device: PopupDevice, isMobile: boolean): boolean {
    switch (device) {
        case 'all':
            return true;
        case 'mobile':
            return isMobile;
        case 'desktop':
            return !isMobile;
        default:
            return false;
    }
}
