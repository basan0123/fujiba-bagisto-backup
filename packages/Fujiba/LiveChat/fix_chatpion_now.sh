#!/bin/bash
# ============================================================
# RUN ON FUJIBA.SHOP SERVER (SSH to r9382355@web1105)
# Fixes livechat_messages table + Api.php in ONE shot
# ============================================================
set -e

MYSQL="mysql -u hrlc6_chatpiondb -p'JpnMyanmar2025!' hrlc6_chatpion_db"
API=~/public_html/fujiba.shop/application/modules/api/controllers/Api.php

echo "=== 1. Add missing columns to livechat_messages ==="
$MYSQL -e "ALTER TABLE livechat_messages ADD COLUMN message_type varchar(20) DEFAULT 'text';" 2>/dev/null || echo "  message_type exists"
$MYSQL -e "ALTER TABLE livechat_messages ADD COLUMN media_url text DEFAULT NULL;" 2>/dev/null || echo "  media_url exists"
$MYSQL -e "ALTER TABLE livechat_messages ADD COLUMN direction enum('in','out') DEFAULT 'in';" 2>/dev/null || echo "  direction exists"
$MYSQL -e "ALTER TABLE livechat_messages ADD COLUMN sender varchar(20) DEFAULT 'user';" 2>/dev/null || echo "  sender exists"
$MYSQL -e "ALTER TABLE livechat_messages ADD COLUMN status varchar(20) DEFAULT 'delivered';" 2>/dev/null || echo "  status exists"

echo "=== 2. Fix Api.php: bot_postback -> messenger_bot_postback ==="
sed -i "s/'bot_postback'/'messenger_bot_postback'/g" "$API"

echo "=== 3. Fix Api.php: from_user_id column -> subscriber_id ==="
sed -i "s/livechat_messages\.from_user_id/livechat_messages.subscriber_id/g" "$API"

echo "=== 4. Verify ==="
$MYSQL -e "DESCRIBE livechat_messages;" 2>&1 | grep -E "message_type|media_url|direction|sender|status"
grep -c "messenger_bot_postback" "$API"
grep -c "livechat_messages.subscriber_id" "$API"

echo "=== DONE ==="
