<?php
// ================================================================
// REPLACE the livechat_messages() function in:
// ~/public_html/fujiba.shop/application/modules/api/controllers/Api.php
//
// Fixes "only half the conversation loads" — the previous version did
// ORDER BY id ASC LIMIT 100, which returned the OLDEST 100 messages
// and dropped everything newer once a thread crossed 100 messages.
//
// This version returns the most recent `limit` messages (default 100,
// max 500), then reverses them so the client still sees them in
// chronological order. Also accepts `start` for paging older history.
// ================================================================

public function livechat_messages()
{
    $api_key = $this->input->post('api_key');
    $api_key_response = $this->api_key_check($api_key);
    if ($api_key_response['status'] != 'success') {
        header('Content-Type: application/json');
        echo json_encode($api_key_response); exit;
    }

    $user_id       = $api_key_response['user_id'];
    $thread_id     = $this->input->post('thread_id');
    $subscriber_id = $this->input->post('subscriber_id') ?: $this->input->post('from_user_id');
    $start         = max(0, (int) $this->input->post('start'));
    $limit         = (int) $this->input->post('limit');
    if ($limit <= 0 || $limit > 500) $limit = 100;

    if (!$thread_id && !$subscriber_id) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'thread_id or subscriber_id required']); exit;
    }

    // Pull the newest `limit` rows, skipping `start` for "load older".
    $this->db->select('id, subscriber_id as from_user_id, message_content as message,
                        CASE WHEN is_read_by_admin = 0 THEN "in" ELSE "out" END as direction,
                        conversation_time as created_at, fb_page_id');
    $this->db->from('messenger_bot_conversation');

    if ($subscriber_id) {
        $this->db->where('subscriber_id', $subscriber_id);
    }

    $this->db->order_by('id', 'DESC');
    $this->db->limit($limit, $start);

    $query = $this->db->get();
    $messages = $query->result_array();

    // Re-sort ascending so the client renders oldest → newest.
    $messages = array_reverse($messages);

    // Mark as read in livechat_thread (only on the first page)
    if ($thread_id && $start === 0) {
        $this->db->where('thread_id', $thread_id);
        $this->db->update('livechat_thread', ['is_read' => '1', 'unread_count' => '0']);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data'   => $messages ?: [],
        'has_more' => count($messages) === $limit,
    ]);
}

// Also fix livechat_bot_flows - replace 'bot_postback' with 'messenger_bot_postback'
public function livechat_bot_flows()
{
    $api_key = $this->input->get('api_key');
    $api_key_response = $this->api_key_check($api_key);
    if ($api_key_response['status'] != 'success') {
        header('Content-Type: application/json');
        echo json_encode($api_key_response); exit;
    }

    $user_id         = $api_key_response['user_id'];
    $whatsapp_bot_id = $this->input->get('whatsapp_bot_id');

    $this->db->select('postback_id, template_name, bot_name, media_type');
    $this->db->from('messenger_bot_postback');
    $this->db->where('user_id', $user_id);
    $this->db->where('page_id', $whatsapp_bot_id);
    $query = $this->db->get();

    header('Content-Type: application/json');
    echo json_encode($query->result_array() ?: []);
}
