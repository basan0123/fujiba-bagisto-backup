<?php

return [

    'queue' => env('FUJIBA_NOTIFICATION_QUEUE', 'default'),

    'fallback_language' => env('FUJIBA_NOTIFICATION_FALLBACK_LANG', 'en'),

    /*
     * Customer messages for the status changes added with the notification
     * matrix (pending payment, processing). Placed / invoiced / shipped /
     * completed / cancelled / refunded are unaffected by this flag.
     */
    'status_updates' => env('FUJIBA_ORDER_MESSENGER_NOTIFICATIONS', true),

];
