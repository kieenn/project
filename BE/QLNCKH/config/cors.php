<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'broadcasting/auth',], // Add login/logout if they are not under /api

    'allowed_methods' => ['*'], // Or be more specific: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000') // Use an env variable!
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // Or be more specific: ['Content-Type', 'X-Requested-With', 'Accept', 'Authorization', 'X-XSRF-TOKEN']

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // VERY IMPORTANT for SPA authentication!

];
