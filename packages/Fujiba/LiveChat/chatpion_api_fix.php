<?php
// ================================================================
// FIXES for fujiba.shop Chatpion API controller
// Replace the corresponding functions in the Api controller
// ================================================================

// FIX 1: livechat_bot_flows — change 'bot_postback' to 'messenger_bot_postback'
public function livechat_bot_flows()
{
    $api_key = $this->input->get('api_key');
    $api_key_response = $this->api_key_check($api_key);
    if ($api_key_response['status'] != 'success') {
        echo json_encode($api_key_response); exit;
    }

    $user_id         = $api_key_response['user_id'];
    $whatsapp_bot_id = $this->input->get('whatsapp_bot_id');

    // FIX: correct table name
    $where  = ['where' => ['user_id' => $user_id, 'page_id' => $whatsapp_bot_id]];
    $select = 'postback_id, template_name, bot_name, media_type';
    $flows  = $this->basic->get_data('messenger_bot_postback', $where, $select);

    header('Content-Type: application/json');
    echo json_encode($flows ?: []);
}

// FIX 2: livechat_send_bot_flow — change 'bot_postback' to 'messenger_bot_postback'
public function livechat_send_bot_flow()
{
    $api_key = $this->input->post('api_key');
    $api_key_response = $this->api_key_check($api_key);
    if ($api_key_response['status'] != 'success') {
        echo json_encode($api_key_response); exit;
    }

    $user_id         = $api_key_response['user_id'];
    $subscriber_id   = $this->input->post('subscriber_id');
    $postback_id     = $this->input->post('postback_id');
    $whatsapp_bot_id = $this->input->post('whatsapp_bot_id');

    if (!$subscriber_id || !$postback_id) {
        echo json_encode(['status' => 'error', 'message' => 'subscriber_id and postback_id required']); exit;
    }

    $this->load->library('fb_rx_login');

    $bot = $this->basic->get_data('facebook_rx_fb_page_info',
        ['where' => ['id' => $whatsapp_bot_id, 'user_id' => $user_id, 'bot_enabled' => '1']]
    );
    if (empty($bot)) {
        echo json_encode(['status' => 'error', 'message' => 'Bot not found']); exit;
    }

    // FIX: correct table name
    $postback = $this->basic->get_data('messenger_bot_postback',
        ['where' => ['postback_id' => $postback_id, 'user_id' => $user_id]]
    );
    if (empty($postback)) {
        echo json_encode(['status' => 'error', 'message' => 'Postback not found']); exit;
    }

    $page_access_token = $bot[0]['page_access_token'];
    $result = $this->fb_rx_login->send_postback_to_subscriber(
        $page_access_token, $subscriber_id, $postback_id
    );

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'result' => $result]);
}

// FIX 3: livechat_messages — use new columns (thread_id, message, direction, created_at)
public function livechat_messages()
{
    $api_key = $this->input->post('api_key');
    $api_key_response = $this->api_key_check($api_key);
    if ($api_key_response['status'] != 'success') {
        echo json_encode($api_key_response); exit;
    }

    $user_id      = $api_key_response['user_id'];
    $thread_id    = $this->input->post('thread_id');
    $from_user_id = $this->input->post('from_user_id');

    if (!$thread_id && !$from_user_id) {
        echo json_encode(['status' => 'error', 'message' => 'thread_id or from_user_id required']); exit;
    }

    $where = ['where' => []];
    if ($thread_id)    $where['where']['livechat_messages.thread_id']      = $thread_id;
    if ($from_user_id) $where['where']['livechat_messages.subscriber_id']  = $from_user_id;

    $select = 'livechat_messages.id, livechat_messages.thread_id, livechat_messages.subscriber_id as from_user_id,
               livechat_messages.message, livechat_messages.message_type, livechat_messages.media_url,
               livechat_messages.direction, livechat_messages.sender, livechat_messages.status,
               livechat_messages.created_at, livechat_messages.conversation_time';

    $order = ['livechat_messages.id' => 'asc'];

    $messages = $this->basic->get_data('livechat_messages', $where, $select, '', 0, $order);

    // Mark thread as read
    if ($thread_id) {
        $this->basic->update_data('livechat_thread',
            ['thread_id' => $thread_id],
            ['is_read' => '1', 'unread_count' => '0']
        );
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'data' => $messages ?: []]);
}
