<?php

return [
    'default_template' => 'classic',
    'default_color' => '#0f766e',

    // Shown on the privacy page for privacy / grievance requests (DPDP Act).
    'contact_email' => env('PRIVACY_CONTACT_EMAIL', 'privacy@example.com'),

    // Accounts inactive this long get a warning email, then are deleted
    // (with their resumes and photos) if they still don't come back.
    'inactivity' => [
        'warn_after_days' => 53,
        'delete_after_days' => 60,
    ],

    'templates' => [
        'classic' => [
            'name' => 'Classic Sidebar',
            'photo' => true,
            'palettes' => ['#0f766e', '#1e3a8a', '#7c3aed', '#b91c1c', '#334155', '#c2410c'],
        ],
    ],
];
