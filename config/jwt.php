<?php

return [
    'secret' => env('JWT_SECRET') ?: env('APP_KEY'),
    'algorithm' => env('JWT_ALGORITHM', 'HS256'),
    'ttl' => (int) env('JWT_TTL', 60),
];
