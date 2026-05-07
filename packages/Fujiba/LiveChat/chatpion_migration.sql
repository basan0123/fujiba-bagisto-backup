-- ================================================================
-- Run this on the Chatpion server (fujiba.shop) MySQL database
-- Creates livechat_thread table and populates from existing livechat_messages
-- ================================================================

-- 1. Create livechat_thread table
CREATE TABLE IF NOT EXISTS `livechat_thread` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `thread_id` varchar(100) NOT NULL,
    `page_id` varchar(200) NOT NULL,
    `page_table_id` int(11) NOT NULL DEFAULT 0,
    `from_user_id` varchar(100) NOT NULL,
    `from_user` varchar(255) DEFAULT NULL,
    `last_message` text DEFAULT NULL,
    `last_message_time` datetime DEFAULT NULL,
    `is_read` enum('0','1') NOT NULL DEFAULT '0',
    `unread_count` int(11) NOT NULL DEFAULT 0,
    `is_archived` enum('0','1') NOT NULL DEFAULT '0',
    `assigned_to` int(11) DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `thread_id` (`thread_id`),
    KEY `page_id` (`page_id`),
    KEY `from_user_id` (`from_user_id`),
    KEY `is_read` (`is_read`),
    KEY `is_archived` (`is_archived`),
    KEY `last_message_time` (`last_message_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Populate livechat_thread from existing livechat_messages
--    Groups by subscriber_id + fb_page_id to create one thread per conversation
INSERT IGNORE INTO livechat_thread (thread_id, page_id, page_table_id, from_user_id, from_user, last_message, last_message_time, is_read, unread_count)
SELECT
    CONCAT(lm.subscriber_id, '_', lm.fb_page_id) AS thread_id,
    lm.fb_page_id AS page_id,
    lm.page_table_id,
    lm.subscriber_id AS from_user_id,
    COALESCE(sub.full_name, lm.subscriber_id) AS from_user,
    SUBSTRING(latest.message_content, 1, 100) AS last_message,
    latest.conversation_time AS last_message_time,
    CASE WHEN latest.message_status = 'read' THEN '1' ELSE '0' END AS is_read,
    (SELECT COUNT(*) FROM livechat_messages lm2
     WHERE lm2.subscriber_id = lm.subscriber_id
     AND lm2.fb_page_id = lm.fb_page_id
     AND lm2.sender = 'user'
     AND (lm2.message_status IS NULL OR lm2.message_status != 'read')
    ) AS unread_count
FROM livechat_messages lm
INNER JOIN (
    SELECT subscriber_id, fb_page_id, MAX(id) AS max_id
    FROM livechat_messages
    GROUP BY subscriber_id, fb_page_id
) grp ON lm.subscriber_id = grp.subscriber_id AND lm.fb_page_id = grp.fb_page_id AND lm.id = grp.max_id
INNER JOIN livechat_messages latest ON latest.id = grp.max_id
LEFT JOIN messenger_bot_subscriber sub ON sub.subscribe_id = lm.subscriber_id AND sub.page_id = lm.fb_page_id
GROUP BY lm.subscriber_id, lm.fb_page_id;

-- 3. Add thread_id and direction columns to livechat_messages if missing
ALTER TABLE `livechat_messages`
    ADD COLUMN IF NOT EXISTS `thread_id` varchar(100) DEFAULT NULL AFTER `id`,
    ADD COLUMN IF NOT EXISTS `message` text DEFAULT NULL AFTER `message_content`,
    ADD COLUMN IF NOT EXISTS `message_type` varchar(20) DEFAULT 'text' AFTER `message`,
    ADD COLUMN IF NOT EXISTS `media_url` text DEFAULT NULL AFTER `message_type`,
    ADD COLUMN IF NOT EXISTS `direction` enum('in','out') DEFAULT 'in' AFTER `media_url`,
    ADD COLUMN IF NOT EXISTS `status` varchar(20) DEFAULT NULL AFTER `direction`,
    ADD COLUMN IF NOT EXISTS `created_at` datetime DEFAULT NULL AFTER `from_business_suite`;

-- 4. Backfill thread_id
UPDATE livechat_messages
SET thread_id = CONCAT(subscriber_id, '_', fb_page_id)
WHERE thread_id IS NULL;

-- 5. Backfill direction from sender column
UPDATE livechat_messages
SET direction = CASE WHEN sender = 'user' THEN 'in' ELSE 'out' END
WHERE direction IS NULL OR direction = '';

-- 6. Backfill created_at from conversation_time
UPDATE livechat_messages
SET created_at = conversation_time
WHERE created_at IS NULL;

-- 7. Backfill plain text message from message_content JSON
--    For incoming (user) messages: extract .entry[0].messaging[0].message.text
--    For outgoing (bot) messages: extract .message.text
UPDATE livechat_messages
SET message = CASE
    WHEN sender = 'user' THEN
        JSON_UNQUOTE(JSON_EXTRACT(message_content, '$.entry[0].messaging[0].message.text'))
    WHEN sender IN ('bot','system') THEN
        JSON_UNQUOTE(JSON_EXTRACT(message_content, '$.message.text'))
    ELSE NULL
END
WHERE message IS NULL AND message_content IS NOT NULL AND message_content != '';

-- 8. Add index on thread_id
ALTER TABLE `livechat_messages` ADD INDEX IF NOT EXISTS `idx_thread_id` (`thread_id`);

SELECT 'Migration complete' AS status,
       (SELECT COUNT(*) FROM livechat_thread) AS threads_created,
       (SELECT COUNT(*) FROM livechat_messages WHERE thread_id IS NOT NULL) AS messages_with_thread;
