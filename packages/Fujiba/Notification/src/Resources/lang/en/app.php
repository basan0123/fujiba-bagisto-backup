<?php

return [

    'menu' => [
        'title' => 'Notifications',
    ],

    'page' => [
        'title'             => 'Notification Templates',
        'tab_templates'     => 'Templates',
        'tab_admin_alerts'  => 'Admin Alerts',
    ],

    'events'        => 'Events',
    'coming_soon'   => 'soon',
    'last_updated'  => 'Last updated',
    'new_template'  => 'New template (not yet saved)',

    'event' => [
        'order.placed'    => 'Order Placed',
        'order.invoiced'  => 'Order Invoiced',
        'order.shipped'   => 'Order Shipped',
        'order.cancelled' => 'Order Cancelled',
        'order.refunded'  => 'Order Refunded',
    ],

    'channel' => [
        'mail'      => 'Email',
        'messenger' => 'Messenger',
        'line'      => 'LINE',
        'whatsapp'  => 'WhatsApp',
        'telegram'  => 'Telegram',
        'instagram' => 'Instagram',
        'tiktok'    => 'TikTok',
    ],

    'recipient' => [
        'label'     => 'Recipient:',
        'customer'  => 'Customer',
        'admin'     => 'Admin Alert',
    ],

    'store' => [
        'label'         => 'Store:',
        'default_all'   => 'Default (all)',
    ],

    'pill' => [
        'store'        => 'store:',
        'recipient'    => 'recipient:',
        'last_updated' => 'last updated',
        'new_template' => 'new template (not yet saved)',
    ],

    'is_active'      => 'Active',
    'from_name'      => 'From name',
    'from_email'     => 'From email',
    'subject'        => 'Subject',
    'body'           => 'Body',
    'save'           => 'Save',
    'delete'         => 'Delete',
    'confirm_delete' => 'Delete this template?',
    'saved'          => 'Template saved.',
    'deleted'        => 'Template deleted.',
    'toggled'        => 'Status updated.',

    'buttons' => [
        'label_max'       => 'Buttons (max 3 — Messenger limit)',
        'btn_label'       => 'Label (max 20 chars)',
        'btn_value_url'   => 'URL or payload',
        'btn_url'         => 'URL',
        'btn_postback'    => 'Postback',
        'btn_phone'       => 'Phone',
        'add_button'      => '+ Add button',
        'vars_for_button' => 'Variables for button URL/value:',
        'max_3_alert'     => 'Messenger allows a maximum of 3 buttons.',
    ],

    'recipients' => [
        'page_title'    => 'Admin Alerts',
        'heading'       => 'Admin Alert Recipients',
        'description'   => 'PSIDs that receive admin alert messages when an order event fires. The Admin Alert template on the Templates tab determines what is sent. Each recipient is scoped to a specific Bagisto channel (or "Default (all)" to receive alerts for every channel).',
        'channel'       => 'Channel',
        'page_id'       => 'Page ID',
        'page_id_ph'    => 'e.g. 592609133927678',
        'psid'          => 'PSID',
        'psid_ph'       => "Admin's Messenger PSID",
        'recipient_label'   => 'Label',
        'recipient_label_ph'=> 'e.g. Owner / Manager',
        'add'               => '+ Add',
        'col_label'         => 'Label',
        'col_active'        => 'Active',
        'pill_active'       => 'ACTIVE',
        'pill_inactive'     => 'inactive',
        'remove'            => 'Remove',
        'confirm_remove'    => 'Remove this recipient?',
        'empty'             => 'No admin recipients configured yet. Add one above to start receiving order alerts in Messenger.',
    ],
];
