<?php

// Peticiones desde la versión web de AgroLink (GitHub Pages u otro dominio).
// La app web usa token Bearer, no cookies, así que no hace falta supports_credentials.
// CORS_ALLOWED_ORIGINS: lista separada por comas; por defecto cualquier origen.
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '*'))))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => false,
];
