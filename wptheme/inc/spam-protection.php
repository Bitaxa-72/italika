<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_spam_get_client_ip')) {
	function italika_spam_get_client_ip() {
		$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

		return preg_match('/^[0-9a-fA-F:\.]+$/', $ip) ? $ip : 'unknown';
	}
}

if (!function_exists('italika_spam_get_client_fingerprint')) {
	function italika_spam_get_client_fingerprint() {
		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

		return md5(italika_spam_get_client_ip() . '|' . $user_agent);
	}
}

if (!function_exists('italika_spam_get_user_agent')) {
	function italika_spam_get_user_agent() {
		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

		return trim((string) $user_agent);
	}
}

if (!function_exists('italika_spam_is_known_good_bot')) {
	function italika_spam_is_known_good_bot() {
		$user_agent = strtolower(italika_spam_get_user_agent());

		if ($user_agent === '') {
			return false;
		}

		$allowed_bots = [
			'googlebot',
			'adsbot-google',
			'apis-google',
			'mediapartners-google',
			'bingbot',
			'yandexbot',
			'duckduckbot',
			'applebot',
		];

		foreach ($allowed_bots as $bot) {
			if (strpos($user_agent, $bot) !== false) {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('italika_spam_is_suspicious_user_agent')) {
	function italika_spam_is_suspicious_user_agent() {
		if (italika_spam_is_known_good_bot()) {
			return false;
		}

		$user_agent = strtolower(italika_spam_get_user_agent());

		if ($user_agent === '') {
			return true;
		}

		$patterns = [
			'curl',
			'wget',
			'python-requests',
			'python/',
			'httpclient',
			'go-http-client',
			'java/',
			'libwww-perl',
			'scrapy',
			'crawler',
			'crawler4j',
			'spider',
			'pythonurllib',
			'aiohttp',
			'guzzlehttp',
			'axios',
			'node-fetch',
			'okhttp',
			'headlesschrome',
			'phantomjs',
			'puppeteer',
			'playwright',
			'selenium',
			'postmanruntime',
			'insomnia',
		];

		foreach ($patterns as $pattern) {
			if (strpos($user_agent, $pattern) !== false) {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('italika_spam_get_browser_cookie_name')) {
	function italika_spam_get_browser_cookie_name() {
		return 'italika_browser_key';
	}
}

if (!function_exists('italika_spam_get_browser_cookie_value')) {
	function italika_spam_get_browser_cookie_value($date = '') {
		$date = $date !== '' ? $date : gmdate('Y-m-d');
		$user_agent = italika_spam_get_user_agent();

		if ($user_agent === '') {
			return '';
		}

		return hash_hmac('sha256', $date . '|' . $user_agent, wp_salt('auth'));
	}
}

if (!function_exists('italika_spam_has_valid_browser_cookie')) {
	function italika_spam_has_valid_browser_cookie() {
		$cookie_name = italika_spam_get_browser_cookie_name();
		$cookie_value = isset($_COOKIE[$cookie_name]) ? sanitize_text_field(wp_unslash($_COOKIE[$cookie_name])) : '';

		if ($cookie_value === '') {
			return false;
		}

		$valid_values = [
			italika_spam_get_browser_cookie_value(gmdate('Y-m-d')),
			italika_spam_get_browser_cookie_value(gmdate('Y-m-d', time() - DAY_IN_SECONDS)),
		];

		return in_array($cookie_value, array_filter($valid_values), true);
	}
}

if (!function_exists('italika_spam_issue_browser_cookie')) {
	function italika_spam_issue_browser_cookie() {
		if (is_admin() || headers_sent() || italika_spam_get_user_agent() === '') {
			return;
		}

		$cookie_name = italika_spam_get_browser_cookie_name();
		$cookie_value = italika_spam_get_browser_cookie_value();

		if ($cookie_value === '') {
			return;
		}

		if (italika_spam_has_valid_browser_cookie()) {
			return;
		}

		$secure = is_ssl();
		$expires = time() + DAY_IN_SECONDS;

		if (PHP_VERSION_ID >= 70300) {
			setcookie($cookie_name, $cookie_value, [
				'expires' => $expires,
				'path' => COOKIEPATH ? COOKIEPATH : '/',
				'domain' => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
				'secure' => $secure,
				'httponly' => true,
				'samesite' => 'Lax',
			]);
			return;
		}

		setcookie(
			$cookie_name,
			$cookie_value,
			$expires,
			(COOKIEPATH ? COOKIEPATH : '/') . '; samesite=Lax',
			COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
			$secure,
			true
		);
	}
}

if (!function_exists('italika_spam_is_protected_content_request')) {
	function italika_spam_is_protected_content_request() {
		if (is_admin() || italika_spam_is_known_good_bot()) {
			return false;
		}

		if (function_exists('is_shop') && is_shop()) {
			return true;
		}

		if (function_exists('is_product') && is_product()) {
			return true;
		}

		if (function_exists('is_product_taxonomy') && is_product_taxonomy()) {
			return true;
		}

		if (function_exists('is_search') && is_search()) {
			return true;
		}

		return false;
	}
}

if (!function_exists('italika_spam_block_request')) {
	function italika_spam_block_request($message, $status = 403) {
		status_header((int) $status);
		nocache_headers();
		wp_die(esc_html($message), esc_html__('Access denied', 'italika'), ['response' => (int) $status]);
	}
}

if (!function_exists('italika_spam_protect_ajax_request')) {
	function italika_spam_protect_ajax_request($action, $limit = 30, $window = 300) {
		if (italika_spam_is_known_good_bot()) {
			return true;
		}

		if (italika_spam_is_suspicious_user_agent()) {
			wp_send_json_error(['message' => 'Доступ ограничен.'], 403);
		}

		if (!italika_spam_has_valid_browser_cookie()) {
			wp_send_json_error(['message' => 'Сессия устарела. Обновите страницу и повторите попытку.'], 403);
		}

		$rate_check = italika_spam_rate_limit('scrape_' . sanitize_key((string) $action), $limit, $window, 'Слишком много запросов. Подождите немного и повторите действие.');

		if (is_wp_error($rate_check)) {
			wp_send_json_error(['message' => $rate_check->get_error_message()], 429);
		}

		return true;
	}
}

add_action('send_headers', 'italika_spam_issue_browser_cookie', 5);

add_action('template_redirect', function () {
	if (!italika_spam_is_protected_content_request()) {
		return;
	}

	if (italika_spam_is_suspicious_user_agent()) {
		italika_spam_block_request('Доступ ограничен.', 403);
	}

	$rate_check = italika_spam_rate_limit('content_pages', 120, 300, 'Слишком много запросов. Подождите немного и обновите страницу.');

	if (is_wp_error($rate_check)) {
		italika_spam_block_request($rate_check->get_error_message(), 429);
	}
}, 1);

if (!function_exists('italika_spam_rate_limit')) {
	function italika_spam_rate_limit($action, $limit, $window, $message = '') {
		$action = sanitize_key((string) $action);
		$limit = max(1, (int) $limit);
		$window = max(10, (int) $window);
		$message = $message !== '' ? $message : 'Слишком много попыток. Подождите немного и повторите.';

		$key = 'italika_rl_' . md5($action . '|' . italika_spam_get_client_fingerprint());
		$data = get_transient($key);

		if (!is_array($data)) {
			$data = [
				'count' => 0,
			];
		}

		$data['count'] = isset($data['count']) ? (int) $data['count'] + 1 : 1;
		set_transient($key, $data, $window);

		if ($data['count'] > $limit) {
			return new WP_Error('italika_rate_limited', $message);
		}

		return true;
	}
}

if (!function_exists('italika_spam_render_fields')) {
	function italika_spam_render_fields($form_key) {
		$form_key = sanitize_key((string) $form_key);
		$rendered_at = time();
		$nonce = wp_create_nonce('italika_spam_' . $form_key . '_' . $rendered_at);
		?>
		<div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
			<label for="<?php echo esc_attr('italika-hp-' . $form_key); ?>">Не заполнять</label>
			<input
				id="<?php echo esc_attr('italika-hp-' . $form_key); ?>"
				type="text"
				name="<?php echo esc_attr('italika_hp_' . $form_key); ?>"
				value=""
				tabindex="-1"
				autocomplete="new-password">
		</div>
		<input type="hidden" name="<?php echo esc_attr('italika_spam_time_' . $form_key); ?>" value="<?php echo esc_attr((string) $rendered_at); ?>">
		<input type="hidden" name="<?php echo esc_attr('italika_spam_nonce_' . $form_key); ?>" value="<?php echo esc_attr($nonce); ?>">
		<?php
	}
}

if (!function_exists('italika_spam_validate_form')) {
	function italika_spam_validate_form($form_key, $args = []) {
		$form_key = sanitize_key((string) $form_key);
		$args = wp_parse_args($args, [
			'min_delay' => 2,
			'max_age' => DAY_IN_SECONDS,
			'rate_limit_action' => '',
			'rate_limit_limit' => 0,
			'rate_limit_window' => 0,
			'message' => 'Проверка формы не пройдена. Обновите страницу и попробуйте еще раз.',
		]);

		$honeypot = isset($_POST['italika_hp_' . $form_key]) ? trim((string) wp_unslash($_POST['italika_hp_' . $form_key])) : '';
		$rendered_at = isset($_POST['italika_spam_time_' . $form_key]) ? (int) wp_unslash($_POST['italika_spam_time_' . $form_key]) : 0;
		$nonce = isset($_POST['italika_spam_nonce_' . $form_key]) ? sanitize_text_field(wp_unslash($_POST['italika_spam_nonce_' . $form_key])) : '';
		$now = time();

		if ($honeypot !== '') {
			return new WP_Error('italika_spam_honeypot', $args['message']);
		}

		if ($rendered_at <= 0 || $nonce === '' || !wp_verify_nonce($nonce, 'italika_spam_' . $form_key . '_' . $rendered_at)) {
			return new WP_Error('italika_spam_nonce', $args['message']);
		}

		if (($now - $rendered_at) < (int) $args['min_delay']) {
			return new WP_Error('italika_spam_too_fast', $args['message']);
		}

		if (($now - $rendered_at) > (int) $args['max_age']) {
			return new WP_Error('italika_spam_expired', 'Форма устарела. Обновите страницу и попробуйте еще раз.');
		}

		if (!empty($args['rate_limit_action']) && !empty($args['rate_limit_limit']) && !empty($args['rate_limit_window'])) {
			return italika_spam_rate_limit(
				$args['rate_limit_action'],
				(int) $args['rate_limit_limit'],
				(int) $args['rate_limit_window'],
				'Слишком много попыток. Подождите немного и повторите действие.'
			);
		}

		return true;
	}
}

add_filter('woocommerce_add_to_cart_validation', function ($passed) {
	if (!$passed) {
		return false;
	}

	$rate_check = italika_spam_rate_limit('add_to_cart', 20, 60, 'Слишком много добавлений в корзину за короткое время.');

	if (is_wp_error($rate_check)) {
		if (function_exists('wc_add_notice')) {
			wc_add_notice($rate_check->get_error_message(), 'error');
		}

		return false;
	}

	return true;
}, 5);

add_action('woocommerce_after_checkout_validation', function ($data, $errors) {
	$check = italika_spam_validate_form('checkout', [
		'min_delay' => 2,
		'max_age' => DAY_IN_SECONDS,
		'rate_limit_action' => 'checkout',
		'rate_limit_limit' => 15,
		'rate_limit_window' => 900,
		'message' => 'Проверка заказа не пройдена. Обновите страницу и попробуйте еще раз.',
	]);

	if (is_wp_error($check)) {
		$errors->add('italika_checkout_spam', $check->get_error_message());
	}
}, 1, 2);

add_filter('woocommerce_process_login_errors', function ($errors) {
	$check = italika_spam_validate_form('login', [
		'min_delay' => 2,
		'max_age' => DAY_IN_SECONDS,
		'rate_limit_action' => 'login',
		'rate_limit_limit' => 8,
		'rate_limit_window' => 600,
		'message' => 'Проверка входа не пройдена. Обновите страницу и попробуйте еще раз.',
	]);

	if (is_wp_error($check)) {
		$errors->add('italika_login_spam', $check->get_error_message());
	}

	return $errors;
});

add_filter('woocommerce_registration_errors', function ($errors) {
	$check = italika_spam_validate_form('register', [
		'min_delay' => 2,
		'max_age' => DAY_IN_SECONDS,
		'rate_limit_action' => 'register',
		'rate_limit_limit' => 5,
		'rate_limit_window' => 3600,
		'message' => 'Проверка регистрации не пройдена. Обновите страницу и попробуйте еще раз.',
	]);

	if (is_wp_error($check)) {
		$errors->add('italika_register_spam', $check->get_error_message());
	}

	return $errors;
}, 5);

add_action('lostpassword_post', function ($errors) {
	$check = italika_spam_validate_form('lost_password', [
		'min_delay' => 2,
		'max_age' => DAY_IN_SECONDS,
		'rate_limit_action' => 'lost_password',
		'rate_limit_limit' => 5,
		'rate_limit_window' => 3600,
		'message' => 'Проверка запроса не пройдена. Обновите страницу и попробуйте еще раз.',
	]);

	if (is_wp_error($check)) {
		$errors->add('italika_lost_password_spam', $check->get_error_message());
	}
}, 1);

add_action('validate_password_reset', function ($errors) {
	$check = italika_spam_validate_form('reset_password', [
		'min_delay' => 2,
		'max_age' => DAY_IN_SECONDS,
		'rate_limit_action' => 'reset_password',
		'rate_limit_limit' => 5,
		'rate_limit_window' => 3600,
		'message' => 'Проверка смены пароля не пройдена. Обновите страницу и попробуйте еще раз.',
	]);

	if (is_wp_error($check)) {
		$errors->add('italika_reset_password_spam', $check->get_error_message());
	}
}, 1);

add_action('admin_menu', function () {
	if (current_user_can('manage_options')) {
		return;
	}

	remove_menu_page('index.php');
	remove_menu_page('upload.php');
	remove_menu_page('edit-comments.php');
}, 999);
