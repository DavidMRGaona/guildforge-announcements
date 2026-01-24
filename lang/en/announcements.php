<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation' => 'Announcements',
    'navigation_group' => 'Communication',

    // Model labels
    'model' => [
        'singular' => 'Announcement',
        'plural' => 'Announcements',
    ],

    // Fields
    'fields' => [
        'title' => 'Title',
        'content' => 'Content',
        'visibility' => 'Visibility',
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

    // Permissions
    'permissions' => [
        'view_any' => 'View announcements list',
        'view' => 'View announcement detail',
        'create' => 'Create announcements',
        'update' => 'Edit announcements',
        'delete' => 'Delete announcements',
    ],

    // Settings
    'settings' => [
        'display' => 'Display',
        'display_description' => 'Configure how announcements are displayed on the site.',
        'show_banner' => 'Show banner',
        'show_banner_help' => 'Displays announcements as a banner at the top of the site.',
        'banner_position' => 'Banner position',
        'position_top' => 'Top',
        'position_bottom' => 'Bottom',
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
];
