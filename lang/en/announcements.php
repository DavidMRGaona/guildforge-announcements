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

    // Fields
    'fields' => [
        'title' => 'Title',
        'content' => 'Content',
        'visibility' => 'Visibility',
        'visibility_help' => '"Members only" is shown just to users holding the association member role chosen in Site Settings → Authentication.',
        'priority' => 'Priority',
        'starts_at' => 'Start date',
        'ends_at' => 'End date',
        'is_active' => 'Active',
        'created_at' => 'Created',
        'updated_at' => 'Updated',
    ],

    // Visibility options
    'visibility' => [
        'public' => 'Public',
        'authenticated' => 'Authenticated users',
        'members' => 'Members only',
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

    // Position options
    'position' => [
        'before_header' => 'Before the header',
        'after_header' => 'After the header',
        'before_content' => 'Before the content',
        'after_content' => 'After the content',
        'before_footer' => 'Before the footer',
        'after_footer' => 'After the footer',
    ],

    // Permissions
    'permissions' => [
        'view_any' => 'View announcements list',
        'create' => 'Create announcements',
        'update' => 'Edit announcements',
        'delete' => 'Delete announcements',
    ],

    // Settings
    'settings' => [
        'display' => 'Display',
        'display_description' => 'Configure how announcements are displayed on the site.',
        'show_banner' => 'Show banner',
        'show_banner_help' => 'Displays active announcements as a banner at the position chosen on each announcement.',
        'auto_rotate' => 'Auto rotate',
        'auto_rotate_help' => 'Automatically rotate between active announcements.',
        'rotate_interval' => 'Rotation interval',
        'rotate_interval_help' => 'Time between announcement changes (in milliseconds).',
    ],

    // Filters
    'filters' => [
        'visibility' => 'Visibility',
        'is_active' => 'Status',
        'active' => 'Active',
        'inactive' => 'Inactive',
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
    ],
];
