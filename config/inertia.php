<?php

/*
 * Only the page-component locations are overridden here; every other
 * Inertia option keeps the package default (vendor/inertiajs/inertia-laravel/config/inertia.php).
 */
return [

    'pages' => [

        'ensure_pages_exist' => false,

        // Public React pages (resources/js/pages) and the React admin app
        // (resources/js/admin/pages). Also used by assertInertia() in tests.
        'paths' => [
            resource_path('js/pages'),
            resource_path('js/admin/pages'),
            resource_path('js/booking/pages'),
        ],

        'extensions' => [
            'tsx',
            'ts',
        ],

    ],

];
