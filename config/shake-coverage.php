<?php

return [
    'client_id' => env('SHAKE_DRIVE_CLIENT_ID'),
    'client_secret' => env('SHAKE_DRIVE_CLIENT_SECRET'),
    'mail_enabled' => env('SHAKE_COVERAGE_EMAIL_ENABLED', false),
    'whatsapp' => [
        'version' => env('SHAKE_WHATSAPP_API_VERSION'),
        'phone_id' => env('SHAKE_WHATSAPP_PHONE_ID'),
        'token' => env('SHAKE_WHATSAPP_TOKEN'),
        'template' => env('SHAKE_WHATSAPP_TEMPLATE'),
    ],
];
