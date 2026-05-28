<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_cookie_consent_cookie_domain')) {
	function italika_cookie_consent_cookie_domain() {
		$host = wp_parse_url(home_url(), PHP_URL_HOST);
		if (!$host || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
			return '';
		}

		$parts = explode('.', $host);
		if (count($parts) < 2) {
			return '';
		}

		return '.' . implode('.', array_slice($parts, -2));
	}
}

if (!function_exists('italika_cookie_consent_get_ip')) {
	function italika_cookie_consent_get_ip() {
		$keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
		foreach ($keys as $key) {
			$value = isset($_SERVER[$key]) ? (string) wp_unslash($_SERVER[$key]) : '';
			if ($value === '') {
				continue;
			}

			$ip = trim(explode(',', $value)[0]);
			if (filter_var($ip, FILTER_VALIDATE_IP)) {
				return $ip;
			}
		}

		return '';
	}
}

if (!function_exists('italika_cookie_consent_set_cookie')) {
	function italika_cookie_consent_set_cookie($consent_id, $status) {
		$domain = italika_cookie_consent_cookie_domain();
		$args = [
			'expires' => time() + YEAR_IN_SECONDS,
			'path' => '/',
			'secure' => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		];

		if ($domain !== '') {
			$args['domain'] = $domain;
		}

		$value = sanitize_key((string) $status) . ':' . sanitize_key((string) $consent_id);
		setcookie('italika_cookie_consent', $value, $args);
		$_COOKIE['italika_cookie_consent'] = $value;
	}
}

if (!function_exists('italika_cookie_consent_has_choice')) {
	function italika_cookie_consent_has_choice() {
		$value = isset($_COOKIE['italika_cookie_consent']) ? sanitize_text_field(wp_unslash($_COOKIE['italika_cookie_consent'])) : '';
		if ($value === '') {
			return false;
		}

		$parts = explode(':', $value, 2);
		return in_array($parts[0] ?? '', ['accepted', 'necessary'], true) && !empty($parts[1]);
	}
}

if (!function_exists('italika_cookie_consent_log')) {
	function italika_cookie_consent_log($status) {
		$status = sanitize_key((string) $status);
		if (!in_array($status, ['accepted', 'necessary'], true)) {
			return new WP_Error('italika_cookie_consent_bad_status', 'Некорректный статус согласия.');
		}

		$ip = italika_cookie_consent_get_ip();
		$record = [
			'id' => wp_generate_uuid4(),
			'status' => $status,
			'time' => current_time('mysql'),
			'timestamp' => time(),
			'user_id' => get_current_user_id(),
			'ip_hash' => $ip !== '' ? hash_hmac('sha256', $ip, wp_salt('auth')) : '',
			'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 220) : '',
			'page' => isset($_POST['page_url']) ? esc_url_raw(wp_unslash($_POST['page_url'])) : '',
		];

		$records = get_option('italika_cookie_consent_records', []);
		if (!is_array($records)) {
			$records = [];
		}

		array_unshift($records, $record);
		$records = array_slice($records, 0, 1000);
		update_option('italika_cookie_consent_records', $records, false);

		italika_cookie_consent_set_cookie($record['id'], $status);

		return $record;
	}
}

add_action('wp_ajax_italika_cookie_consent', 'italika_cookie_consent_ajax');
add_action('wp_ajax_nopriv_italika_cookie_consent', 'italika_cookie_consent_ajax');

if (!function_exists('italika_cookie_consent_ajax')) {
	function italika_cookie_consent_ajax() {
		check_ajax_referer('italika_cookie_consent', 'nonce');

		$status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : '';
		$record = italika_cookie_consent_log($status);

		if (is_wp_error($record)) {
			wp_send_json_error(['message' => $record->get_error_message()], 400);
		}

		wp_send_json_success([
			'id' => $record['id'],
			'status' => $record['status'],
		]);
	}
}

add_action('admin_menu', function () {
	add_options_page(
		'Согласия cookie',
		'Согласия cookie',
		'manage_options',
		'italika-cookie-consent',
		'italika_cookie_consent_admin_page'
	);
});

if (!function_exists('italika_cookie_consent_admin_page')) {
	function italika_cookie_consent_admin_page() {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Недостаточно прав.', 'italika'));
		}

		$records = get_option('italika_cookie_consent_records', []);
		if (!is_array($records)) {
			$records = [];
		}

		?>
		<div class="wrap">
			<h1>Согласия cookie</h1>
			<p>Хранятся последние 1000 решений. IP сохраняется только в виде хеша, без исходного адреса.</p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Дата</th>
						<th>Статус</th>
						<th>ID согласия</th>
						<th>Пользователь</th>
						<th>IP hash</th>
						<th>Страница</th>
						<th>User-Agent</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!$records) : ?>
						<tr><td colspan="7">Записей пока нет.</td></tr>
					<?php endif; ?>
					<?php foreach ($records as $record) : ?>
						<tr>
							<td><?php echo esc_html((string) ($record['time'] ?? '')); ?></td>
							<td><?php echo esc_html(($record['status'] ?? '') === 'accepted' ? 'Приняты все' : 'Только необходимые'); ?></td>
							<td><code><?php echo esc_html((string) ($record['id'] ?? '')); ?></code></td>
							<td><?php echo esc_html(!empty($record['user_id']) ? (string) $record['user_id'] : 'гость'); ?></td>
							<td><code><?php echo esc_html(mb_substr((string) ($record['ip_hash'] ?? ''), 0, 16)); ?></code></td>
							<td><?php echo esc_html((string) ($record['page'] ?? '')); ?></td>
							<td><?php echo esc_html((string) ($record['user_agent'] ?? '')); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}

add_action('wp_footer', function () {
	if (is_admin() || italika_cookie_consent_has_choice()) {
		return;
	}

	?>
	<div class="cookie-consent" id="cookie-consent" role="dialog" aria-live="polite" aria-label="Согласие на cookies">
		<div class="cookie-consent__body">
			<div>
				<strong class="cookie-consent__title">Мы используем cookies</strong>
				<p class="cookie-consent__text">Необходимые cookies нужны для работы сайта, корзины и выбора версии сайта. Аналитические и дополнительные cookies помогают улучшать новый сайт.</p>
			</div>
			<div class="cookie-consent__actions">
				<button type="button" class="cookie-consent__button cookie-consent__button--primary" data-cookie-consent="accepted">Принять</button>
				<button type="button" class="cookie-consent__button" data-cookie-consent="necessary">Только необходимые</button>
			</div>
		</div>
	</div>
	<style>
		.cookie-consent{position:fixed;left:16px;right:16px;bottom:16px;z-index:99998;display:flex;justify-content:center}
		.cookie-consent__body{width:min(860px,100%);display:flex;align-items:center;justify-content:space-between;gap:18px;padding:16px 18px;background:#fff;border:1px solid #d9d2c4;border-radius:8px;box-shadow:0 16px 48px rgba(0,0,0,.16)}
		.cookie-consent__title{display:block;margin-bottom:4px;color:#1f2a1f;font-size:16px}
		.cookie-consent__text{margin:0;color:#554d43;font-size:14px;line-height:1.4}
		.cookie-consent__actions{display:flex;gap:10px;flex-shrink:0}
		.cookie-consent__button{min-height:40px;padding:8px 14px;border:1px solid #d9d2c4;border-radius:8px;background:#fff;color:#2c3a27;font-weight:700;cursor:pointer}
		.cookie-consent__button--primary{background:#2f6b35;border-color:#2f6b35;color:#fff}
		@media (max-width:720px){.cookie-consent__body{display:grid}.cookie-consent__actions{display:grid}.cookie-consent__button{width:100%}}
	</style>
	<script>
		(function () {
			var box = document.getElementById('cookie-consent');
			if (!box) {
				return;
			}

			function sendConsent(status) {
				var data = new FormData();
				data.append('action', 'italika_cookie_consent');
				data.append('nonce', '<?php echo esc_js(wp_create_nonce('italika_cookie_consent')); ?>');
				data.append('status', status);
				data.append('page_url', window.location.href);

				box.style.pointerEvents = 'none';
				fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
					method: 'POST',
					credentials: 'same-origin',
					body: data
				})
					.then(function (response) {
						return response.json();
					})
					.then(function (response) {
						if (!response || !response.success) {
							throw new Error('Не удалось сохранить согласие.');
						}

						box.remove();
					})
					.catch(function () {
						box.style.pointerEvents = '';
					});
			}

			box.addEventListener('click', function (event) {
				var button = event.target.closest('[data-cookie-consent]');
				if (!button) {
					return;
				}

				sendConsent(button.getAttribute('data-cookie-consent'));
			});
		})();
	</script>
	<?php
});
