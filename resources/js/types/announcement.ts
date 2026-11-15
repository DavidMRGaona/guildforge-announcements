import type { ResolvedSiteBlock } from '@/types/siteBlocks';

export type AnnouncementVisibility = 'public' | 'authenticated' | 'members' | 'guests';

export type AnnouncementPosition =
    | 'before-header'
    | 'after-header'
    | 'before-content'
    | 'after-content'
    | 'before-footer'
    | 'after-footer';

export type AnnouncementFormat = 'banner' | 'popup';

export type PopupShape = 'centered' | 'corner';

export type PopupPages = 'all' | 'home' | 'paths';

export type PopupDevice = 'all' | 'mobile' | 'desktop';

/** How often a window returns to the same person (announcements#68) */
export type PopupFrequency = 'once' | 'session' | 'days';

export interface PopupTriggers {
    /** Seconds after the page loads (0–60); null when not used */
    delay_seconds: number | null;
    /** Share of the page scrolled (10–100); null when not used */
    scroll_percent: number | null;
    /** When the mouse leaves through the top of the page (desktop only) */
    exit_intent: boolean;
    /** Pages seen in this visit, this one included (2–20); null when not used */
    page_views: number | null;
}

export interface PopupAction {
    label: string;
    /** An https address or a path of this site starting with "/" (checked by the server) */
    url: string;
}

export interface AnnouncementPopup {
    shape: PopupShape;
    image_public_id: string | null;
    /** What the image shows; null when it is only decoration (empty `alt`) */
    image_alt: string | null;
    action: PopupAction | null;
    triggers: PopupTriggers;
    pages: PopupPages;
    /** Only with pages "paths": "/eventos", or "/eventos*" for everything that starts so */
    paths: string[];
    device: PopupDevice;
    frequency: PopupFrequency;
    /** Only with frequency "days": 1–365 */
    frequency_days: number | null;
    /**
     * A site block of the core or of another module, resolved by the server for this person
     * (announcements#70); null for a window without one, or when it cannot be drawn and the window
     * is offered anyway
     */
    block: ResolvedSiteBlock | null;
}

export interface Announcement {
    id: string;
    title: string;
    content: string;
    visibility: AnnouncementVisibility;
    position: AnnouncementPosition;
    priority: number;
    background_color: string | null;
    text_color: string | null;
    starts_at: string | null;
    ends_at: string | null;
    is_active: boolean;
    is_dismissible: boolean;
    created_at: string | null;
    updated_at: string | null;
    format: AnnouncementFormat;
    /** How the window opens; null for a banner */
    popup: AnnouncementPopup | null;
}

/** An announcement in the popup format, as the server shares them (prop `announcementPopups`) */
export type PopupAnnouncement = Announcement & {
    format: 'popup';
    popup: AnnouncementPopup;
    /**
     * This account has already seen it and it is not due again. The server keeps sharing it so the
     * windows offered do not change when one opens; the browser just does not open it.
     */
    seen: boolean;
};

/** How a window was closed: its ✕, Esc or the backdrop, or its button */
export type PopupCloseReason = 'dismiss' | 'action';

export interface AnnouncementSettings {
    show_banner: boolean;
    auto_rotate: boolean;
    rotate_interval: number;
}
