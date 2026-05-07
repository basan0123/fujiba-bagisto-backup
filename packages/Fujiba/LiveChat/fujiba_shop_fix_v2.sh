#!/bin/bash
# ================================================================
# Run on fujiba.shop server (r9382355@web1105)
# Fixes 2 critical issues in Api.php:
#   1) bot_postback -> messenger_bot_postback (table name)
#   2) livechat_messages.from_user_id -> livechat_messages.subscriber_id (column name)
# ================================================================
set -e

API_FILE=~/public_html/fujiba.shop/application/modules/api/controllers/Api.php

echo "=== Current state ==="
grep -n "bot_postback\|from_user_id" "$API_FILE" | head -20

echo ""
echo "=== FIX 1: bot_postback -> messenger_bot_postback ==="
if grep -q "'bot_postback'" "$API_FILE"; then
    sed -i "s/'bot_postback'/'messenger_bot_postback'/g" "$API_FILE"
    echo "Done: bot_postback replaced"
else
    echo "Already fixed or not found"
fi

echo ""
echo "=== FIX 2: livechat_messages.from_user_id -> livechat_messages.subscriber_id ==="
if grep -q "livechat_messages.from_user_id" "$API_FILE"; then
    sed -i "s/livechat_messages\.from_user_id/livechat_messages.subscriber_id/g" "$API_FILE"
    echo "Done: from_user_id replaced with subscriber_id"
else
    echo "Already fixed or not found"
fi

echo ""
echo "=== Verify ==="
grep -n "messenger_bot_postback\|subscriber_id" "$API_FILE" | head -20

echo ""
echo "=== DONE ==="
