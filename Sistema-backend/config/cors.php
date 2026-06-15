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

        // URL DE PRODUCCIÓN DE TU FRONTEND EN RAILWAY
        'https://positive-courtesy-production-e5a2.up.railway.app',

        // DOMINIO PROPIO (S.D.B.)
        'https://sdb-vial.online',
        'https://www.sdb-vial.online',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
