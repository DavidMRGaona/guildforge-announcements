<?php

declare(strict_types=1);

return [
    'categories' => [
        'section' => 'Announcements',
        'new_announcements' => [
            'label' => 'New announcements',
            'description' => 'A notice in the bell when an announcement you can see is published.',
        ],
    ],

    'subjects' => [
        'announcement' => 'announcement',
    ],

    'title' => 'New announcement: :title',
    'grouped' => '{1} :count new announcement|[2,*] :count new announcements',
];
