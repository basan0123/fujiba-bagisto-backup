<?php

return [
    [
        // Group inside emails.general — sits next to "Notifications" group.
        // Renders as a card on /admin/configuration/emails/general.
        'key'  => 'emails.general.fujiba_notification',
        'name' => 'Fujiba Notifications',
        'info' => 'Master kill-switch for the Fujiba notification engine (email + Messenger, per-channel templates, admin alerts). To author template content and manage admin recipients, use the "Notifications" entry in the left sidebar (or visit /admin/fujiba-notifications directly).',
        'sort' => 50,

        'fields' => [
            [
                'name'    => 'emails.general.fujiba_notification.enabled',
                'title'   => 'Enable Fujiba Notifications',
                'type'    => 'boolean',
                'default' => 1,
                'info'    => 'When OFF, no Fujiba notifications fire on any order event for any channel — neither customer nor admin alerts. Use as a kill-switch during incident response or when migrating systems.',
            ],
        ],
    ],
];
