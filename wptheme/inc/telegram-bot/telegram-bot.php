<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_tg_bot_token')) {
	function italika_tg_bot_token() {
		if (defined('ITALIKA_TG_BOT_TOKEN')) {
			return trim((string) ITALIKA_TG_BOT_TOKEN);
		}

		$env_token = getenv('ITALIKA_TG_BOT_TOKEN');

		return is_string($env_token) ? trim($env_token) : '';
	}
}

if (!function_exists('italika_tg_webhook_secret')) {
	function italika_tg_webhook_secret() {
		if (defined('ITALIKA_TG_WEBHOOK_SECRET')) {
			return trim((string) ITALIKA_TG_WEBHOOK_SECRET);
		}

		$env_secret = getenv('ITALIKA_TG_WEBHOOK_SECRET');

		return is_string($env_secret) ? trim($env_secret) : '';
	}
}

if (!function_exists('italika_tg_active_chats')) {
	function italika_tg_active_chats() {
		$chats = get_option('italika_tg_active_chats', []);

		return is_array($chats) ? $chats : [];
	}
}

if (!function_exists('italika_tg_save_active_chats')) {
	function italika_tg_save_active_chats($chats) {
		update_option('italika_tg_active_chats', is_array($chats) ? $chats : [], false);
	}
}

if (!function_exists('italika_tg_chat_key')) {
	function italika_tg_chat_key($chat_id) {
		return preg_replace('/[^0-9-]/', '', (string) $chat_id);
	}
}

if (!function_exists('italika_tg_send_message')) {
	function italika_tg_send_message($chat_id, $text, $args = []) {
		$token = italika_tg_bot_token();
		$chat_id = italika_tg_chat_key($chat_id);

		if ($token === '' || $chat_id === '' || trim((string) $text) === '') {
			return false;
		}

		$payload = array_merge([
			'chat_id' => $chat_id,
			'text' => (string) $text,
			'parse_mode' => 'HTML',
			'disable_web_page_preview' => true,
		], $args);

		$response = wp_remote_post('https://api.telegram.org/bot' . $token . '/sendMessage', [
			'timeout' => 10,
			'headers' => [
				'Content-Type' => 'application/json; charset=utf-8',
			],
			'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		]);

		if (is_wp_error($response)) {
			error_log('Italika Telegram bot error: ' . $response->get_error_message());
			return false;
		}

		$status = (int) wp_remote_retrieve_response_code($response);

		if ($status < 200 || $status >= 300) {
			error_log('Italika Telegram bot HTTP error: ' . $status . ' ' . wp_remote_retrieve_body($response));
			return false;
		}

		return true;
	}
}

if (!function_exists('italika_tg_split_message')) {
	function italika_tg_split_message($message, $limit = 3800) {
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

if (!function_exists('italika_tg_format_money')) {
	function italika_tg_format_money($amount, $currency = '') {
		if (function_exists('wc_price')) {
			$price = wp_strip_all_tags(wc_price((float) $amount, $currency ? ['currency' => $currency] : []));
			$charset = function_exists('get_bloginfo') ? get_bloginfo('charset') : 'UTF-8';
			$price = html_entity_decode($price, ENT_QUOTES | ENT_HTML5, $charset ?: 'UTF-8');
			$price = str_replace(chr(194) . chr(160), ' ', $price);
			$price = preg_replace('/\s+/u', ' ', trim($price));

			return $price;
		}

		return number_format((float) $amount, 2, ',', ' ') . ($currency ? ' ' . $currency : '');
	}
}

if (!function_exists('italika_tg_order_items_lines')) {
	function italika_tg_order_items_lines(WC_Order $order) {
		$lines = [];
		$currency = $order->get_currency();

		foreach ($order->get_items() as $item) {
			if (!$item instanceof WC_Order_Item_Product) {
				continue;
			}

			$product = $item->get_product();
			$sku = $product ? $product->get_sku() : '';
			$name = $item->get_name();
			$qty = (float) $item->get_quantity();
			$total = italika_tg_format_money($item->get_total(), $currency);
			$meta_parts = [];

			foreach ($item->get_formatted_meta_data('') as $meta) {
				$key = wp_strip_all_tags($meta->display_key);
				$value = wp_strip_all_tags($meta->display_value);

				if ($key !== '' && $value !== '') {
					$meta_parts[] = $key . ': ' . $value;
				}
			}

			$line = '• ' . $name . ($sku ? ' [' . $sku . ']' : '') . ' x ' . $qty . ' = ' . $total;

			if ($meta_parts) {
				$line .= ' (' . implode('; ', $meta_parts) . ')';
			}

			$lines[] = $line;
		}

		return $lines;
	}
}

if (!function_exists('italika_tg_shipping_title')) {
	function italika_tg_shipping_title(WC_Order $order) {
		$methods = [];

		foreach ($order->get_shipping_methods() as $method) {
			$title = $method->get_name();

			if ($title !== '') {
				$methods[] = $title;
			}
		}

		return implode(', ', array_unique($methods));
	}
}

if (!function_exists('italika_tg_format_order_message')) {
	function italika_tg_format_order_message(WC_Order $order) {
		$currency = $order->get_currency();
		$lines = [];
		$items = italika_tg_order_items_lines($order);
		$shipping_title = italika_tg_shipping_title($order);
		$payment_title = $order->get_payment_method_title();
		$admin_url = admin_url('post.php?post=' . $order->get_id() . '&action=edit');
		$comment = trim((string) $order->get_customer_note());
		$customer_details = function_exists('italika_wc_get_order_customer_details') ? italika_wc_get_order_customer_details($order) : [
			'customer_type' => 'individual',
			'company_name' => '',
			'company_inn' => '',
			'bonus_card_number' => '',
		];
		$customer_type = $customer_details['customer_type'];
		$company_name = $customer_details['company_name'];
		$company_inn = $customer_details['company_inn'];
		$bonus_card_number = $customer_details['bonus_card_number'];
		$customer_type_label = function_exists('italika_wc_get_customer_type_label') ? italika_wc_get_customer_type_label($customer_type) : 'Физ. лицо';

		$lines[] = '<b>Новый заказ #' . esc_html($order->get_order_number()) . '</b>';
		$lines[] = 'Статус: ' . esc_html(wc_get_order_status_name($order->get_status()));
		$lines[] = 'Сумма: <b>' . esc_html(italika_tg_format_money($order->get_total(), $currency)) . '</b>';
		$lines[] = '';
		$lines[] = '<b>Клиент</b>';
		$lines[] = 'Имя: ' . esc_html($order->get_billing_first_name() ?: '-');
		$lines[] = 'Телефон: ' . esc_html($order->get_billing_phone() ?: '-');

		if ($order->get_billing_email()) {
			$lines[] = 'Email: ' . esc_html($order->get_billing_email());
		}

		$lines[] = 'Тип: ' . esc_html($customer_type_label);
		$lines[] = 'Фамилия: ' . esc_html($order->get_billing_last_name() ?: '-');

		if ($customer_type === 'legal_entity' && $company_name !== '') {
			$lines[] = 'Компания: ' . esc_html($company_name);
		}

		if ($customer_type === 'legal_entity' && $company_inn !== '') {
			$lines[] = 'ИНН: ' . esc_html($company_inn);
		}

		if ($bonus_card_number !== '') {
			$lines[] = 'Бонусная карта: ' . esc_html($bonus_card_number);
		}

		$lines[] = '';
		$lines[] = '<b>Получение и оплата</b>';
		$lines[] = 'Получение: ' . esc_html($shipping_title ?: '-');

		if ($order->get_billing_city()) {
			$lines[] = 'Город: ' . esc_html($order->get_billing_city());
		}

		if ($order->get_billing_address_1()) {
			$lines[] = 'Адрес/ПВЗ: ' . esc_html($order->get_billing_address_1());
		}

		$lines[] = 'Оплата: ' . esc_html($payment_title ?: '-');

		if ($comment !== '') {
			$lines[] = 'Комментарий: ' . esc_html($comment);
		}

		$lines[] = '';
		$lines[] = '<b>Состав</b>';
		$lines = array_merge($lines, array_map('esc_html', $items));
		$lines[] = '';
		$lines[] = 'Товары: ' . esc_html(italika_tg_format_money($order->get_subtotal(), $currency));
		$lines[] = 'Доставка: ' . esc_html(italika_tg_format_money($order->get_shipping_total(), $currency));
		$lines[] = 'Итого: <b>' . esc_html(italika_tg_format_money($order->get_total(), $currency)) . '</b>';
		$lines[] = '';
		$lines[] = '<a href="' . esc_url($admin_url) . '">Открыть заказ в админке</a>';

		return implode("\n", $lines);
	}
}

if (!function_exists('italika_tg_notify_order')) {
	function italika_tg_notify_order($order_id) {
		if (!function_exists('wc_get_order')) {
			return;
		}

		$order = wc_get_order($order_id);

		if (!$order || $order->get_meta('_italika_tg_order_sent') === 'yes') {
			return;
		}

		$chats = italika_tg_active_chats();

		if (!$chats) {
			return;
		}

		$message = italika_tg_format_order_message($order);
		$sent = false;

		foreach (array_keys($chats) as $chat_id) {
			foreach (italika_tg_split_message($message) as $chunk) {
				$sent = italika_tg_send_message($chat_id, $chunk) || $sent;
			}
		}

		if ($sent) {
			$order->update_meta_data('_italika_tg_order_sent', 'yes');
			$order->save();
		}
	}
}

add_action('woocommerce_checkout_order_processed', 'italika_tg_notify_order', 40, 1);

if (!function_exists('italika_tg_verify_webhook')) {
	function italika_tg_verify_webhook(WP_REST_Request $request) {
		$secret = italika_tg_webhook_secret();

		if (italika_tg_bot_token() === '' || $secret === '') {
			return false;
		}

		$header_secret = (string) $request->get_header('x-telegram-bot-api-secret-token');

		return hash_equals($secret, $header_secret);
	}
}

if (!function_exists('italika_tg_register_chat')) {
	function italika_tg_register_chat($chat) {
		$chat_id = isset($chat['id']) ? italika_tg_chat_key($chat['id']) : '';

		if ($chat_id === '') {
			return false;
		}

		$title = $chat['title'] ?? trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? ''));
		$chats = italika_tg_active_chats();
		$chats[$chat_id] = [
			'title' => sanitize_text_field((string) $title),
			'type' => sanitize_text_field((string) ($chat['type'] ?? '')),
			'started_at' => time(),
		];

		italika_tg_save_active_chats($chats);

		return $chat_id;
	}
}

if (!function_exists('italika_tg_unregister_chat')) {
	function italika_tg_unregister_chat($chat) {
		$chat_id = isset($chat['id']) ? italika_tg_chat_key($chat['id']) : '';

		if ($chat_id === '') {
			return false;
		}

		$chats = italika_tg_active_chats();
		unset($chats[$chat_id]);
		italika_tg_save_active_chats($chats);

		return $chat_id;
	}
}

if (!function_exists('italika_tg_extract_message')) {
	function italika_tg_extract_message($update) {
		foreach (['message', 'edited_message', 'channel_post'] as $key) {
			if (!empty($update[$key]) && is_array($update[$key])) {
				return $update[$key];
			}
		}

		return [];
	}
}

if (!function_exists('italika_tg_handle_webhook')) {
	function italika_tg_handle_webhook(WP_REST_Request $request) {
		$update = $request->get_json_params();
		$message = is_array($update) ? italika_tg_extract_message($update) : [];
		$chat = $message['chat'] ?? [];
		$text = trim((string) ($message['text'] ?? ''));

		if (!$chat || $text === '') {
			return rest_ensure_response(['ok' => true]);
		}

		if (preg_match('/^\/start(?:@\w+)?(?:\s|$)/i', $text)) {
			$chat_id = italika_tg_register_chat($chat);

			if ($chat_id) {
				italika_tg_send_message($chat_id, "Бот включен. Новые заказы будут приходить в этот чат.\nОстановить: /stop");
			}

			return rest_ensure_response(['ok' => true]);
		}

		if (preg_match('/^\/stop(?:@\w+)?(?:\s|$)/i', $text)) {
			$chat_id = isset($chat['id']) ? italika_tg_chat_key($chat['id']) : '';

			if ($chat_id !== '') {
				italika_tg_send_message($chat_id, 'Бот остановлен. Новые заказы больше не будут приходить в этот чат.');
				italika_tg_unregister_chat($chat);
			}

			return rest_ensure_response(['ok' => true]);
		}

		return rest_ensure_response(['ok' => true]);
	}
}

add_action('rest_api_init', function () {
	register_rest_route('italika/v1', '/telegram/webhook', [
		'methods' => WP_REST_Server::CREATABLE,
		'callback' => 'italika_tg_handle_webhook',
		'permission_callback' => 'italika_tg_verify_webhook',
	]);
});
