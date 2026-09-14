<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jalur yang Dilindungi CORS
    |--------------------------------------------------------------------------
    |
    | Panel admin memakai sesi (Inertia), aplikasi POS mobile/web memakai token
    | bearer di bawah `/api/mobile/v1/*`. Keduanya lewat `api/*`.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Header yang Diizinkan
    |--------------------------------------------------------------------------
    |
    | `Authorization` WAJIB disebut sendiri, walaupun ada `*`. Menurut aturan
    | CORS, wildcard tidak mencakup header itu.
    |
    | Tanpa baris ini gejalanya menyesatkan: login dari aplikasi web berhasil
    | (permintaan login belum membawa token), lalu SETIAP permintaan sesudahnya
    | ditolak browser pada preflight — dan aplikasi membacanya sebagai "tidak
    | bisa terhubung ke server". Di Android hal ini tidak terasa karena tidak
    | ada preflight.
    |
    */

    'allowed_headers' => ['*', 'Authorization'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
