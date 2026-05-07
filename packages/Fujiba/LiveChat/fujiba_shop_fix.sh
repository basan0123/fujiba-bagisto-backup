#!/bin/bash
# ================================================================
# Run this on the fujiba.shop server (r9382355@web1105)
# Fixes: livechat_thread table + columns + postback table name
# ================================================================
set -e

DB_USER="hrlc6_fujiba_user"
DB_PASS="Fujiba@202501"
DB_HOST="mysql1026.onamae.ne.jp"
DB_NAME="hrlc6_chatpion_db"
MYSQL="mysql -u $DB_USER -p$DB_PASS -h $DB_HOST $DB_NAME"

echo "=== FIX 1: Create livechat_thread table ==="
$MYSQL << 'SQL'
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
    KEY `last_message_time` (`last_message_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
echo "Done: livechat_thread created"

echo ""
echo "=== FIX 2: Check livechat_messages columns ==="
$MYSQL -e "DESCRIBE livechat_messages;"

echo ""
echo "=== FIX 3: Add missing columns to livechat_messages ==="
$MYSQL << 'SQL'
ALTER TABLE livechat_messages
  ADD COLUMN IF NOT EXISTS `thread_id` varchar(100) DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `message` text DEFAULT NULL AFTER `message_content`,
  ADD COLUMN IF NOT EXISTS `message_type` varchar(20) DEFAULT 'text' AFTER `message`,
  ADD COLUMN IF NOT EXISTS `media_url` text DEFAULT NULL AFTER `message_type`,
  ADD COLUMN IF NOT EXISTS `direction` enum('in','out') DEFAULT 'in' AFTER `media_url`,
  ADD COLUMN IF NOT EXISTS `status` varchar(20) DEFAULT NULL AFTER `direction`,
  ADD COLUMN IF NOT EXISTS `created_at` datetime DEFAULT NULL;
SQL
echo "Done: columns added"

echo ""
echo "=== FIX 4: Backfill thread_id ==="
$MYSQL -e "UPDATE livechat_messages SET thread_id = CONCAT(subscriber_id, '_', fb_page_id) WHERE thread_id IS NULL;"
echo "Done: thread_id backfilled"

echo ""
echo "=== FIX 5: Backfill direction from sender ==="
$MYSQL -e "UPDATE livechat_messages SET direction = CASE WHEN sender = 'user' THEN 'in' ELSE 'out' END WHERE direction IS NULL OR direction = '';"
echo "Done: direction backfilled"

echo ""
echo "=== FIX 6: Backfill created_at ==="
$MYSQL -e "UPDATE livechat_messages SET created_at = conversation_time WHERE created_at IS NULL;"
echo "Done: created_at backfilled"

echo ""
echo "=== FIX 7: Extract plain text message from JSON message_content ==="
$MYSQL << 'SQL'
UPDATE livechat_messages
SET message = CASE
    WHEN sender = 'user' THEN
        JSON_UNQUOTE(JSON_EXTRACT(message_content, '$.entry[0].messaging[0].message.text'))
    WHEN sender IN ('bot','system') THEN
        JSON_UNQUOTE(JSON_EXTRACT(message_content, '$.message.text'))
    ELSE NULL
END
WHERE message IS NULL AND message_content IS NOT NULL AND message_content != '';
SQL
echo "Done: message text extracted"

echo ""
echo "=== FIX 8: Add index on thread_id ==="
$MYSQL -e "ALTER TABLE livechat_messages ADD INDEX IF NOT EXISTS idx_thread_id (thread_id);" 2>/dev/null || echo "Index may already exist"
echo "Done"

echo ""
echo "=== FIX 9: Backfill livechat_thread from livechat_messages ==="
$MYSQL << 'SQL'
INSERT IGNORE INTO livechat_thread (thread_id, page_id, page_table_id, from_user_id, from_user, last_message, last_message_time, is_read, unread_count)
SELECT
    CONCAT(lm.subscriber_id, '_', lm.fb_page_id) AS thread_id,
    lm.fb_page_id AS page_id,
    lm.page_table_id,
    lm.subscriber_id AS from_user_id,
    COALESCE(sub.full_name, lm.subscriber_id) AS from_user,
    SUBSTRING(latest_msg.message, 1, 100) AS last_message,
    latest_msg.conversation_time AS last_message_time,
    '1' AS is_read,
    0 AS unread_count
FROM (
    SELECT subscriber_id, fb_page_id, MAX(id) AS max_id
    FROM livechat_messages
    WHERE subscriber_id IS NOT NULL AND subscriber_id != ''
    GROUP BY subscriber_id, fb_page_id
) grp
INNER JOIN livechat_messages lm ON lm.id = grp.max_id
INNER JOIN livechat_messages latest_msg ON latest_msg.id = grp.max_id
LEFT JOIN messenger_bot_subscriber sub
    ON sub.subscribe_id = lm.subscriber_id
    AND sub.page_id = lm.fb_page_id
;
SQL
echo "Done: livechat_thread backfilled"

echo ""
echo "=== FIX 10: Fix bot_postback -> messenger_bot_postback in Api.php ==="
API_FILE=~/public_html/fujiba.shop/application/modules/api/controllers/Api.php
if grep -q "'bot_postback'" "$API_FILE"; then
    sed -i "s/'bot_postback'/'messenger_bot_postback'/g" "$API_FILE"
    echo "Done: bot_postback replaced"
else
    echo "Already fixed or not found"
fi
grep -n "messenger_bot_postback" "$API_FILE"

echo ""
echo "=== FIX 11: Verify ==="
$MYSQL -e "SELECT COUNT(*) AS thread_count FROM livechat_thread;"
$MYSQL -e "SELECT COUNT(*) AS msg_with_thread FROM livechat_messages WHERE thread_id IS NOT NULL;"
$MYSQL -e "SELECT COUNT(*) AS msg_with_text FROM livechat_messages WHERE message IS NOT NULL;"

echo ""
echo "=== ALL DONE ==="
