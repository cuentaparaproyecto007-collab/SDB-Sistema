<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    // ✅ 'api/*' ya cubre perfectamente tu ruta '/api/login'
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // 🐳 PUERTOS OBLIGATORIOS PARA DOCKER (NGINX)
        'http://localhost',
        'http://127.0.0.1',
        
        // 💻 Tus puertos antiguos de desarrollo local (Vite)
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:5174', 
        'http://127.0.0.1:5174',
        'http://localhost:8000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
