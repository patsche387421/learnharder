<?php

// CORS für die Vue-App: nur die eigenen Origins, mit Cookies (Sanctum-SPA-Modus).
// Produktion: CORS_ALLOWED_ORIGINS=https://app.learnharder.app

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
