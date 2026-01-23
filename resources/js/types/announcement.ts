export type AnnouncementVisibility = 'public' | 'authenticated' | 'members';

export type AnnouncementPosition =
    | 'before-header'
    | 'after-header'
    | 'before-content'
    | 'after-content'
    | 'before-footer'
    | 'after-footer';

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
}

export interface AnnouncementSettings {
    show_banner: boolean;
    banner_position: 'top' | 'bottom';
    auto_rotate: boolean;
    rotate_interval: number;
}
