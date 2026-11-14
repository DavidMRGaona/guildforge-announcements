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
export type PopupAnnouncement = Announcement & { format: 'popup'; popup: AnnouncementPopup };

/** How a window was closed: its ✕, Esc or the backdrop, or its button */
export type PopupCloseReason = 'dismiss' | 'action';

export interface AnnouncementSettings {
    show_banner: boolean;
    auto_rotate: boolean;
    rotate_interval: number;
}
