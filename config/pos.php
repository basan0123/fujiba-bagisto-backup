<?php

// config/pos.php

return [
    // POS-specific JWT signing secret. Set to a value different from JWT_SECRET
    // to cryptographically isolate POS tokens from other application tokens.
    // Requires `php artisan jwt:secret` equivalent for this key.
    'jwt_secret' => env('POS_JWT_SECRET'),

    // Token lifetime in minutes. Default covers an 8-hour shift.
    'token_ttl' => (int) env('POS_TOKEN_TTL', 480),
];
