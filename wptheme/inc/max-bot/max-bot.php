<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_max_bot_token')) {
	function italika_max_bot_token() {
		if (defined('ITALIKA_MAX_BOT_TOKEN')) {
			return trim((string) ITALIKA_MAX_BOT_TOKEN);
		}

		$env_token = getenv('ITALIKA_MAX_BOT_TOKEN');

		return is_string($env_token) ? trim($env_token) : '';
	}
}

if (!function_exists('italika_max_webhook_secret')) {
	function italika_max_webhook_secret() {
		if (defined('ITALIKA_MAX_WEBHOOK_SECRET')) {
			return trim((string) ITALIKA_MAX_WEBHOOK_SECRET);
		}

		$env_secret = getenv('ITALIKA_MAX_WEBHOOK_SECRET');

		return is_string($env_secret) ? trim($env_secret) : '';
	}
}

if (!function_exists('italika_max_active_chats')) {
	function italika_max_active_chats() {
		$chats = get_option('italika_max_active_chats', []);

		return is_array($chats) ? $chats : [];
	}
}

if (!function_exists('italika_max_save_active_chats')) {
	function italika_max_save_active_chats($chats) {
		update_option('italika_max_active_chats', is_array($chats) ? $chats : [], false);
	}
}

if (!function_exists('italika_max_id_key')) {
	function italika_max_id_key($id) {
		return preg_replace('/[^0-9-]/', '', (string) $id);
	}
}

if (!function_exists('italika_max_destination_key')) {
	function italika_max_destination_key($type, $id) {
		$type = $type === 'user' ? 'user' : 'chat';
		$id = italika_max_id_key($id);

		return $id === '' ? '' : $type . ':' . $id;
	}
}

if (!function_exists('italika_max_destination_from_update')) {
	function italika_max_destination_from_update($update) {
		$chat_id = isset($update['chat_id']) ? italika_max_id_key($update['chat_id']) : '';

		if ($chat_id !== '') {
			return [
				'type' => 'chat',
				'id' => $chat_id,
			];
		}

		$message = isset($update['message']) && is_array($update['message']) ? $update['message'] : [];
		$recipient = isset($message['recipient']) && is_array($message['recipient']) ? $message['recipient'] : [];
		$recipient_chat_id = isset($recipient['chat_id']) ? italika_max_id_key($recipient['chat_id']) : '';

		if ($recipient_chat_id !== '') {
			return [
				'type' => 'chat',
				'id' => $recipient_chat_id,
			];
		}

		$sender = isset($message['sender']) && is_array($message['sender']) ? $message['sender'] : [];
		$user_id = isset($sender['user_id']) ? italika_max_id_key($sender['user_id']) : '';

		if ($user_id !== '') {
			return [
				'type' => 'user',
				'id' => $user_id,
			];
		}

		$user = isset($update['user']) && is_array($update['user']) ? $update['user'] : [];
		$user_id = isset($user['user_id']) ? italika_max_id_key($user['user_id']) : '';

		if ($user_id !== '') {
			return [
				'type' => 'user',
				'id' => $user_id,
			];
		}

		return [];
	}
}

if (!function_exists('italika_max_send_message')) {
	function italika_max_send_message($destination, $text, $args = []) {
		$token = italika_max_bot_token();
		$text = trim((string) $text);

		if ($token === '' || $text === '') {
			return false;
		}

		if (is_array($destination)) {
			$type = isset($destination['type']) && $destination['type'] === 'user' ? 'user' : 'chat';
			$id = isset($destination['id']) ? italika_max_id_key($destination['id']) : '';
		} else {
			$type = 'chat';
			$id = italika_max_id_key($destination);
		}

		if ($id === '') {
			return false;
		}

		$query_arg = $type === 'user' ? 'user_id' : 'chat_id';
		$payload = array_merge([
			'text' => $text,
			'format' => 'html',
			'notify' => true,
		], $args);

		$response = wp_remote_post(add_query_arg($query_arg, $id, 'https://platform-api.max.ru/messages'), [
			'timeout' => 10,
			'headers' => [
				'Authorization' => $token,
				'Content-Type' => 'application/json; charset=utf-8',
			],
			'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		]);

		if (is_wp_error($response)) {
			error_log('Italika MAX bot error: ' . $response->get_error_message());
			return false;
		}

		$status = (int) wp_remote_retrieve_response_code($response);

		if ($status < 200 || $status >= 300) {
			error_log('Italika MAX bot HTTP error: ' . $status . ' ' . wp_remote_retrieve_body($response));
			return false;
		}

		return true;
	}
}

if (!function_exists('italika_max_split_message')) {
	function italika_max_split_message($message, $limit = 3800) {
		if (function_exists('italika_tg_split_message')) {
			return italika_tg_split_message($message, $limit);
		}

		$message = (string) $message;
		$length = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';

		if ($length($message) <= $limit) {
			return [$message];
		}

		$chunks = [];
		$current = '';
		$lines = preg_split('/\R/u', $message);

		foreach ($lines as $line) {
			$next = $current === '' ? $line : $current . "\n" . $line;

			if ($length($next) > $limit) {
				if ($current !== '') {
					$chunks[] = $current;
				}

				$current = $line;
				continue;
			}

			$current = $next;
		}

		if ($current !== '') {
			$chunks[] = $current;
		}

		return $chunks;
	}
}

if (!function_exists('italika_max_format_order_message')) {
	function italika_max_format_order_message(WC_Order $order) {
		if (function_exists('italika_tg_format_order_message')) {
			return italika_tg_format_order_message($order);
		}

		return '<b>Новый заказ #' . esc_html($order->get_order_number()) . '</b>' . "\n"
			. 'Сумма: <b>' . esc_html($order->get_total()) . ' ' . esc_html($order->get_currency()) . '</b>';
	}
}

if (!function_exists('italika_max_notify_order')) {
	function italika_max_notify_order($order_id) {
		if (!function_exists('wc_get_order')) {
			return;
		}

		$order = wc_get_order($order_id);

		if (!$order || $order->get_meta('_italika_max_order_sent') === 'yes') {
			return;
		}

		$chats = italika_max_active_chats();

		if (!$chats) {
			return;
		}

		$message = italika_max_format_order_message($order);
		$sent = false;

		foreach ($chats as $chat) {
			if (!is_array($chat)) {
				continue;
			}

			$destination = [
				'type' => isset($chat['type']) && $chat['type'] === 'user' ? 'user' : 'chat',
				'id' => $chat['id'] ?? '',
			];

			foreach (italika_max_split_message($message) as $chunk) {
				$sent = italika_max_send_message($destination, $chunk) || $sent;
			}
		}

		if ($sent) {
			$order->update_meta_data('_italika_max_order_sent', 'yes');
			$order->save();
		}
	}
}

add_action('woocommerce_checkout_order_processed', 'italika_max_notify_order', 40, 1);

if (!function_exists('italika_max_verify_webhook')) {
	function italika_max_verify_webhook(WP_REST_Request $request) {
		$secret = italika_max_webhook_secret();

		if (italika_max_bot_token() === '' || $secret === '') {
			return false;
		}

		$header_secret = (string) $request->get_header('x-max-bot-api-secret');

		return hash_equals($secret, $header_secret);
	}
}

if (!function_exists('italika_max_register_destination')) {
	function italika_max_register_destination($destination, $title = '') {
		if (empty($destination['id'])) {
			return false;
		}

		$type = isset($destination['type']) && $destination['type'] === 'user' ? 'user' : 'chat';
		$id = italika_max_id_key($destination['id']);
		$key = italika_max_destination_key($type, $id);

		if ($key === '') {
			return false;
		}

		$chats = italika_max_active_chats();
		$chats[$key] = [
			'id' => $id,
			'type' => $type,
			'title' => sanitize_text_field((string) $title),
			'started_at' => time(),
		];

		italika_max_save_active_chats($chats);

		return $destination;
	}
}

if (!function_exists('italika_max_unregister_destination')) {
	function italika_max_unregister_destination($destination) {
		if (empty($destination['id'])) {
			return false;
		}

		$type = isset($destination['type']) && $destination['type'] === 'user' ? 'user' : 'chat';
		$id = italika_max_id_key($destination['id']);
		$key = italika_max_destination_key($type, $id);

		if ($key === '') {
			return false;
		}

		$chats = italika_max_active_chats();
		unset($chats[$key]);
		italika_max_save_active_chats($chats);

		return $destination;
	}
}

if (!function_exists('italika_max_update_text')) {
	function italika_max_update_text($update) {
		$message = isset($update['message']) && is_array($update['message']) ? $update['message'] : $update;
		$body = isset($message['body']) && is_array($message['body']) ? $message['body'] : [];

		if (isset($body['text'])) {
			return trim((string) $body['text']);
		}

		if (isset($message['text'])) {
			return trim((string) $message['text']);
		}

		return '';
	}
}

if (!function_exists('italika_max_destination_title')) {
	function italika_max_destination_title($update) {
		$message = isset($update['message']) && is_array($update['message']) ? $update['message'] : [];
		$recipient = isset($message['recipient']) && is_array($message['recipient']) ? $message['recipient'] : [];

		if (!empty($recipient['title'])) {
			return (string) $recipient['title'];
		}

		$sender = isset($message['sender']) && is_array($message['sender']) ? $message['sender'] : [];
		$user = isset($update['user']) && is_array($update['user']) ? $update['user'] : $sender;
		$name = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));

		if ($name !== '') {
			return $name;
		}

		return (string) ($user['username'] ?? '');
	}
}

if (!function_exists('italika_max_handle_webhook')) {
	function italika_max_handle_webhook(WP_REST_Request $request) {
		$update = $request->get_json_params();

		if (!is_array($update)) {
			return rest_ensure_response(['ok' => true]);
		}

		$update_type = (string) ($update['update_type'] ?? '');
		$text = italika_max_update_text($update);
		$destination = italika_max_destination_from_update($update);

		if (!$destination) {
			return rest_ensure_response(['ok' => true]);
		}

		if ($update_type === 'bot_started' || preg_match('/^\/start(?:\s|$)/i', $text)) {
			$registered = italika_max_register_destination($destination, italika_max_destination_title($update));

			if ($registered) {
				italika_max_send_message($registered, "Бот включен. Новые заказы будут приходить в этот чат.\nОстановить: /stop");
			}

			return rest_ensure_response(['ok' => true]);
		}

		if (preg_match('/^\/stop(?:\s|$)/i', $text)) {
			italika_max_send_message($destination, 'Бот остановлен. Новые заказы больше не будут приходить в этот чат.');
			italika_max_unregister_destination($destination);

			return rest_ensure_response(['ok' => true]);
		}

		return rest_ensure_response(['ok' => true]);
	}
}

add_action('rest_api_init', function () {
	register_rest_route('italika/v1', '/max/webhook', [
		'methods' => WP_REST_Server::CREATABLE,
		'callback' => 'italika_max_handle_webhook',
		'permission_callback' => 'italika_max_verify_webhook',
	]);
});
