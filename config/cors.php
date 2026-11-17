<?php

$frontend = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');

$origins = array_values(array_filter(array_unique([
    $frontend,
    // Local dev defaults
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:8080',
    'http://127.0.0.1:8080',
])));

// Optional comma-separated extras, e.g. preview Pages URLs
$extra = trim((string) env('CORS_ALLOWED_ORIGINS', ''));
if ($extra !== '') {
    foreach (explode(',', $extra) as $origin) {
        $origin = trim($origin);
        if ($origin !== '') {
            $origins[] = rtrim($origin, '/');
        }
    }
    $origins = array_values(array_unique($origins));
}

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['*'],

    /*
    | Bearer-token SPA: allow the configured frontend host(s).
    | Set FRONTEND_URL to your Cloudflare Pages URL in production.
    */
    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [
        '#^https://.*\.pages\.dev$#',
        '#^https://.*\.onrender\.com$#',
        '#^https://.*\.vercel\.app$#',
        '#^https://.*\.netlify\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
