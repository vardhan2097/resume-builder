<?php

return [
    'default_template' => 'classic',
    'default_color' => '#0f766e',

    // Shown on the privacy page for privacy / grievance requests (DPDP Act).
    'contact_email' => env('PRIVACY_CONTACT_EMAIL', ''),

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
        'executive' => [
            'name' => 'Executive',
            'photo' => true,
            'palettes' => ['#1f3a5f', '#334155', '#0f766e', '#4c1d95', '#7c2d12', '#9f1239'],
        ],
        'editorial' => [
            'name' => 'Editorial',
            'photo' => true,
            'palettes' => ['#854d0e', '#334155', '#0f766e', '#7c2d12', '#1e3a8a', '#6b21a8'],
        ],
        'metro' => [
            'name' => 'Metro',
            'photo' => true,
            'palettes' => ['#164e63', '#1e3a8a', '#713f12', '#4c1d95', '#9f1239', '#334155'],
        ],
        'folio' => [
            'name' => 'Folio',
            'photo' => true,
            'palettes' => ['#7c2d12', '#164e63', '#3f6212', '#1e3a8a', '#6b21a8', '#334155'],
        ],
        'studio' => [
            'name' => 'Studio',
            'photo' => true,
            'palettes' => ['#9f1239', '#1d4ed8', '#0f766e', '#6d28d9', '#a16207', '#334155'],
        ],
        'ledger' => [
            'name' => 'Ledger',
            'photo' => true,
            'palettes' => ['#b45309', '#1e40af', '#0f766e', '#7e22ce', '#be123c', '#334155'],
        ],
        'timeline' => [
            'name' => 'Timeline Sidebar',
            'photo' => true,
            'palettes' => ['#123b70', '#0f766e', '#334155', '#6d28d9', '#9f1239', '#854d0e'],
        ],
        'banner' => [
            'name' => 'Banner',
            'photo' => true,
            'palettes' => ['#4338ca', '#1e3a8a', '#0f766e', '#7c3aed', '#be123c', '#334155'],
        ],
    ],
];
