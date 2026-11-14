<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation' => 'Announcements',

    // Model labels
    'model' => [
        'singular' => 'announcement',
        'plural' => 'Announcements',
    ],

    // Sections
    'sections' => [
        'content' => 'Content',
        'display' => 'Display',
        'display_description' => 'Where and how the announcement is shown.',
        'styling' => 'Custom style',
        'styling_description' => 'Leave empty to use the default colours of the priority.',
        'scheduling' => 'Scheduling',
        'popup' => 'Popup window',
        'popup_description' => 'What the window looks like. It can always be closed, with the ✕ or the Esc key.',
        'popup_triggers' => 'When it opens',
        'popup_triggers_description' => 'It opens with the first one met. With none, as soon as the page loads.',
        'popup_where' => 'Where it opens',
    ],

    // Fields
    'fields' => [
        'title' => 'Title',
        'content' => 'Content',
        'visibility' => 'Visibility',
        'visibility_help' => '"Members only" is shown just to users holding the association member role chosen in Site Settings → Authentication. "Only visitors without a session", to whoever has not signed in.',
        'position' => 'Position',
        'priority' => 'Priority',
        'priority_help' => '1 (low) to 10 (high). Sets the display order and the default colour.',
        'background_color' => 'Background colour',
        'background_color_help' => 'Custom background colour of the banner.',
        'text_color' => 'Text colour',
        'text_color_help' => 'Custom text colour of the banner.',
        'starts_at' => 'Start date',
        'ends_at' => 'End date',
        'is_active' => 'Active',
        'is_dismissible' => 'Can be closed',
        'is_dismissible_help' => 'Whether people can close the announcement. Once closed, it does not come back.',
        'created_at' => 'Created',
        'updated_at' => 'Updated',
        'format' => 'Format',
        'format_help' => 'A banner is a band in the chosen position; a popup window opens over the page.',
    ],

    // Visibility options
    'visibility' => [
        'public' => 'Public',
        'authenticated' => 'Authenticated users',
        'members' => 'Members only',
        'guests' => 'Only visitors without a session',
    ],

    // Position options
    'position' => [
        'before_header' => 'Before the header',
        'after_header' => 'After the header',
        'before_content' => 'Before the content',
        'after_content' => 'After the content',
        'before_footer' => 'Before the footer',
        'after_footer' => 'After the footer',
    ],

    // Actions
    'actions' => [
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'activated' => 'Announcement activated',
        'deactivated' => 'Announcement deactivated',
    ],

    // Messages
    'messages' => [
        'created' => 'Announcement created successfully.',
        'updated' => 'Announcement updated successfully.',
        'deleted' => 'Announcement deleted successfully.',
        'not_found' => 'Announcement not found.',
    ],

    // Permissions
    'permissions' => [
        'view_any' => 'View announcements list',
        'create' => 'Create announcements',
        'update' => 'Edit announcements',
        'delete' => 'Delete announcements',
        'settings_manage' => 'Change the banner settings',
    ],

    // Settings
    'settings' => [
        'navigation' => 'Announcements',
        'title' => 'Announcement settings',
        'saved' => 'Settings saved.',
        'save' => 'Save changes',
        'display' => 'Display',
        'display_description' => 'Configure how announcements are displayed on the site.',
        'show_banner' => 'Show banner',
        'show_banner_help' => 'Displays active banner announcements at the position chosen on each one. Popup windows do not depend on this setting.',
        'auto_rotate' => 'Auto rotate',
        'auto_rotate_help' => 'Automatically rotate between active announcements.',
        'rotate_interval' => 'Rotation interval',
        'rotate_interval_help' => 'Time between announcement changes (in milliseconds).',
    ],

    // Filters
    'filters' => [
        'visibility' => 'Visibility',
        'position' => 'Position',
        'is_active' => 'Status',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'format' => 'Format',
    ],

    // Placeholders
    'placeholders' => [
        'title' => 'Announcement title',
        'content' => 'Write the announcement content...',
        'no_date' => 'No date',
    ],

    // Media library (core#482)
    'media' => [
        'source' => 'Announcements',
        'role_in_text' => 'In the text',
        'role_popup_image' => 'Window image',
    ],

    // Formats (announcements#67)
    'format' => [
        'banner' => 'Banner',
        'popup' => 'Popup window',
    ],

    // Popup windows (announcements#67)
    'popup' => [
        'shape' => [
            'label' => 'Shape',
            'help' => 'The centred one covers the page until it is closed; the card sits at the bottom and lets people keep reading.',
            'centered' => 'Centred',
            'corner' => 'Card in the bottom corner',
        ],
        'image' => 'Image',
        'image_help' => 'Optional. Shown above the title.',
        'action_label' => 'Button text',
        'action_label_help' => 'Optional. Without both text and link there is no button.',
        'action_url' => 'Button link',
        'action_url_help' => 'An https address or a path of the site starting with "/", such as /events.',
        'action_url_invalid' => 'The link must be an https address or a path of the site starting with "/".',
        'trigger_delay' => 'On load, after a few seconds',
        'trigger_delay_help' => 'From 0 to 60 seconds. With 0 it opens as soon as the page loads.',
        'trigger_scroll' => 'On scrolling down the page (%)',
        'trigger_scroll_help' => 'From 10 to 100. Only scrolling counts: on a page that cannot be scrolled, this never opens it.',
        'trigger_exit_intent' => 'On leaving',
        'trigger_exit_intent_help' => 'When the mouse leaves through the top of the page. Desktop only: phones use the delay, or 10 seconds without one.',
        'trigger_page_views' => 'After several pages',
        'trigger_page_views_help' => 'From 2 to 20 pages in the same visit.',
        'seconds' => 's',
        'pages' => [
            'label' => 'Pages',
            'help' => 'It never opens in the panel, on sign-in or registration, on legal pages, during maintenance, on /newsletter or on error pages.',
            'all' => 'The whole site',
            'home' => 'Only the home page',
            'paths' => 'Only these paths',
        ],
        'paths' => 'Paths',
        'paths_help' => 'Press Enter after each one. End with * to include what is below: /events* covers /events and every event.',
        'paths_invalid' => 'Every path starts with "/", without domain or "?", and may only have one "*" at the end.',
        'device' => [
            'label' => 'Device',
            'help' => 'A phone is a screen under 768 px wide.',
            'all' => 'All',
            'mobile' => 'Phones only',
            'desktop' => 'Desktop only',
        ],
    ],
];
