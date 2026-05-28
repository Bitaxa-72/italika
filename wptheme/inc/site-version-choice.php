<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_site_choice_old_url')) {
	function italika_site_choice_old_url() {
		return apply_filters('italika_site_choice_old_url', 'https://old.selibox.com/');
	}
}

if (!function_exists('italika_site_choice_cookie_version')) {
	function italika_site_choice_cookie_version() {
		return max(1, (int) get_option('italika_site_choice_cookie_version', 1));
	}
}

if (!function_exists('italika_site_choice_pack_cookie')) {
	function italika_site_choice_pack_cookie($value) {
		return sanitize_key((string) $value) . ':' . italika_site_choice_cookie_version();
	}
}

if (!function_exists('italika_site_choice_read_cookie')) {
	function italika_site_choice_read_cookie() {
		$raw = isset($_COOKIE['italika_site_version']) ? sanitize_text_field(wp_unslash($_COOKIE['italika_site_version'])) : '';
		if ($raw === '') {
			return '';
		}

		$parts = explode(':', $raw, 2);
		$value = sanitize_key($parts[0] ?? '');
		$version = isset($parts[1]) ? (int) $parts[1] : 0;

		if ($version !== italika_site_choice_cookie_version()) {
			return '';
		}

		return in_array($value, ['old', 'new'], true) ? $value : '';
	}
}

if (!function_exists('italika_site_choice_cookie_domain')) {
	function italika_site_choice_cookie_domain() {
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

if (!function_exists('italika_site_choice_set_cookie')) {
	function italika_site_choice_set_cookie($value, $ttl) {
		$domain = italika_site_choice_cookie_domain();
		$args = [
			'expires' => time() + (int) $ttl,
			'path' => '/',
			'secure' => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		];

		if ($domain !== '') {
			$args['domain'] = $domain;
		}

		$packed = italika_site_choice_pack_cookie($value);
		setcookie('italika_site_version', $packed, $args);
		$_COOKIE['italika_site_version'] = $packed;
	}
}

add_filter('allowed_redirect_hosts', function ($hosts) {
	$old_host = wp_parse_url(italika_site_choice_old_url(), PHP_URL_HOST);
	if ($old_host && !in_array($old_host, $hosts, true)) {
		$hosts[] = $old_host;
	}

	return $hosts;
});

add_action('admin_menu', function () {
	add_options_page(
		'Выбор версии сайта',
		'Выбор версии сайта',
		'manage_options',
		'italika-site-version-choice',
		'italika_site_choice_admin_page'
	);
});

if (!function_exists('italika_site_choice_admin_page')) {
	function italika_site_choice_admin_page() {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Недостаточно прав.', 'italika'));
		}

		$message = '';
		if (isset($_POST['italika_site_choice_reset'])) {
			check_admin_referer('italika_site_choice_reset');

			$new_version = italika_site_choice_cookie_version() + 1;
			update_option('italika_site_choice_cookie_version', $new_version, false);
			$message = 'Выбор версии сайта сброшен. Все старые cookie теперь недействительны.';
		}

		?>
		<div class="wrap">
			<h1>Выбор версии сайта</h1>

			<?php if ($message !== '') : ?>
				<div class="notice notice-success"><p><?php echo esc_html($message); ?></p></div>
			<?php endif; ?>

			<table class="widefat striped" style="max-width:760px;">
				<tbody>
					<tr>
						<th scope="row">Старый сайт</th>
						<td><code><?php echo esc_html(italika_site_choice_old_url()); ?></code></td>
					</tr>
					<tr>
						<th scope="row">Текущая версия cookie</th>
						<td><code><?php echo esc_html((string) italika_site_choice_cookie_version()); ?></code></td>
					</tr>
					<tr>
						<th scope="row">Срок выбора старого сайта</th>
						<td>24 часа</td>
					</tr>
					<tr>
						<th scope="row">Срок выбора нового сайта</th>
						<td>96 часов</td>
					</tr>
				</tbody>
			</table>

			<form method="post" style="margin-top:18px;">
				<?php wp_nonce_field('italika_site_choice_reset'); ?>
				<input type="hidden" name="italika_site_choice_reset" value="1">
				<button type="submit" class="button button-primary">Сбросить выбор у всех пользователей</button>
			</form>

			<p style="max-width:760px;">Кнопка не удаляет cookie физически из браузеров, но делает их недействительными. При следующем заходе пользователи снова увидят модалку выбора версии сайта.</p>
		</div>
		<?php
	}
}

if (!function_exists('italika_site_choice_clean_url')) {
	function italika_site_choice_clean_url() {
		$request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';

		return remove_query_arg(['site_version', 'site_choice'], home_url($request_uri));
	}
}

add_action('template_redirect', function () {
	if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
		return;
	}

	$choice = isset($_GET['site_version']) ? sanitize_key(wp_unslash($_GET['site_version'])) : '';
	if ($choice === '') {
		$choice = isset($_GET['site_choice']) ? sanitize_key(wp_unslash($_GET['site_choice'])) : '';
	}

	if ($choice === 'new') {
		italika_site_choice_set_cookie('new', 96 * HOUR_IN_SECONDS);
		wp_safe_redirect(italika_site_choice_clean_url());
		exit;
	}

	if ($choice === 'old') {
		italika_site_choice_set_cookie('old', 24 * HOUR_IN_SECONDS);
		wp_safe_redirect(italika_site_choice_old_url());
		exit;
	}

	$current = italika_site_choice_read_cookie();
	if ($current === 'old') {
		wp_safe_redirect(italika_site_choice_old_url());
		exit;
	}
});

add_action('wp_footer', function () {
	if (is_admin()) {
		return;
	}

	$current = italika_site_choice_read_cookie();
	if ($current === 'old' || $current === 'new') {
		return;
	}

	$new_url = esc_url(add_query_arg('site_version', 'new', home_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/')));
	?>
	<div class="site-choice-modal" id="site-choice-modal" role="dialog" aria-modal="true" aria-labelledby="site-choice-title">
		<div class="site-choice-modal__backdrop"></div>
		<div class="site-choice-modal__panel">
			<h2 class="site-choice-modal__title" id="site-choice-title">Выберите версию сайта</h2>
			<p class="site-choice-modal__text">Мы обновили сайт. Если вам привычнее старая версия, можно временно перейти на нее.</p>
			<div class="site-choice-modal__actions">
				<a class="site-choice-modal__button site-choice-modal__button--primary" href="<?php echo $new_url; ?>">Остаться на новом сайте</a>
				<a class="site-choice-modal__button" href="<?php echo esc_url(add_query_arg('site_version', 'old', home_url('/'))); ?>">Перейти на старый сайт</a>
			</div>
			<p class="site-choice-modal__note">Выбор нового сайта запомнится на 96 часов, старого сайта - на 24 часа.</p>
		</div>
	</div>
	<style>
		.site-choice-modal{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px}
		.site-choice-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.42)}
		.site-choice-modal__panel{position:relative;width:min(460px,100%);background:#fff;border-radius:8px;padding:24px;box-shadow:0 24px 80px rgba(0,0,0,.22);font-family:inherit;text-align:center}
		.site-choice-modal__title{margin:0 0 10px;font-size:24px;line-height:1.2;color:#1f2a1f}
		.site-choice-modal__text{margin:0 0 18px;font-size:15px;line-height:1.45;color:#4f463b}
		.site-choice-modal__actions{display:flex;justify-content:center;gap:10px;flex-wrap:wrap}
		.site-choice-modal__button{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 16px;border:1px solid #d9d2c4;border-radius:8px;background:#fff;color:#2c3a27;text-decoration:none;font-weight:700}
		.site-choice-modal__button--primary{background:#2f6b35;border-color:#2f6b35;color:#fff}
		.site-choice-modal__note{margin:14px 0 0;font-size:13px;line-height:1.35;color:#766b5f}
		@media (max-width:520px){.site-choice-modal{align-items:flex-end;padding:12px}.site-choice-modal__panel{padding:20px}.site-choice-modal__actions{display:grid}.site-choice-modal__button{width:100%}}
	</style>
	<?php
});
