<?php

return [
    /*
     * Primary Chatpion URL (can be overridden per-channel via fujiba_livechat_settings table)
     */
    'chatpion_url'   => env('CHATPION_URL_1', 'https://chatpion1.fujiba.shop'),

    /*
     * Default API key (can be overridden per-channel via settings table)
     */
    'api_key'        => env('CHATPION_API_KEY', ''),

    /*
     * Default Facebook Page ID
     */
    'fb_page_id'     => env('FB_PAGE_ID', ''),
];
