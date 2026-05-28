<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_newsletter_table')) {
	function italika_newsletter_table()
	{
		global $wpdb;

		return $wpdb->prefix . 'italika_newsletter_subscribers';
	}
}

if (!function_exists('italika_newsletter_install')) {
	function italika_newsletter_install()
	{
		global $wpdb;

		$table = italika_newsletter_table();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta("CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(190) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			token varchar(64) NOT NULL,
			source varchar(80) NOT NULL DEFAULT '',
			ip varchar(45) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			unsubscribed_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email),
			KEY status (status),
			KEY token (token)
		) {$charset_collate};");

		update_option('italika_newsletter_db_version', '1.0.0', false);
	}
}

add_action('after_switch_theme', 'italika_newsletter_install');

add_action('admin_init', function () {
	if (get_option('italika_newsletter_db_version') !== '1.0.0') {
		italika_newsletter_install();
	}
});

add_action('init', function () {
	if (get_option('italika_newsletter_db_version') !== '1.0.0') {
		italika_newsletter_install();
	}
}, 1);

if (!function_exists('italika_newsletter_normalize_email')) {
	function italika_newsletter_normalize_email($email)
	{
		return sanitize_email(strtolower(trim((string) $email)));
	}
}

if (!function_exists('italika_newsletter_token')) {
	function italika_newsletter_token()
	{
		return wp_generate_password(48, false, false);
	}
}

if (!function_exists('italika_newsletter_get_by_email')) {
	function italika_newsletter_get_by_email($email)
	{
		global $wpdb;

		$email = italika_newsletter_normalize_email($email);

		if ($email === '') {
			return null;
		}

		return $wpdb->get_row($wpdb->prepare(
			'SELECT * FROM ' . italika_newsletter_table() . ' WHERE email = %s LIMIT 1',
			$email
		));
	}
}

if (!function_exists('italika_newsletter_get_by_token')) {
	function italika_newsletter_get_by_token($token)
	{
		global $wpdb;

		$token = sanitize_text_field((string) $token);

		if ($token === '') {
			return null;
		}

		return $wpdb->get_row($wpdb->prepare(
			'SELECT * FROM ' . italika_newsletter_table() . ' WHERE token = %s LIMIT 1',
			$token
		));
	}
}

if (!function_exists('italika_newsletter_get_ip')) {
	function italika_newsletter_get_ip()
	{
		$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

		return preg_match('/^[0-9a-fA-F:\.]+$/', $ip) ? $ip : '';
	}
}

if (!function_exists('italika_newsletter_upsert')) {
	function italika_newsletter_upsert($email, $source = 'site')
	{
		global $wpdb;

		$email = italika_newsletter_normalize_email($email);

		if (!is_email($email)) {
			return new WP_Error('invalid_email', 'Укажите корректный email.');
		}

		$now = current_time('mysql');
		$existing = italika_newsletter_get_by_email($email);

		if ($existing && $existing->status === 'active') {
			return [
				'subscriber' => $existing,
				'created' => false,
				'reactivated' => false,
			];
		}

		if ($existing) {
			$wpdb->query($wpdb->prepare(
				'UPDATE ' . italika_newsletter_table() . ' SET status = %s, updated_at = %s, unsubscribed_at = NULL, source = %s, ip = %s WHERE id = %d',
				'active',
				$now,
				sanitize_text_field((string) $source),
				italika_newsletter_get_ip(),
				(int) $existing->id
			));

			return [
				'subscriber' => italika_newsletter_get_by_email($email),
				'created' => false,
				'reactivated' => true,
			];
		}

		$wpdb->query($wpdb->prepare(
			'INSERT INTO ' . italika_newsletter_table() . ' (email, status, token, source, ip, created_at, updated_at, unsubscribed_at) VALUES (%s, %s, %s, %s, %s, %s, %s, NULL)',
			$email,
			'active',
			italika_newsletter_token(),
			sanitize_text_field((string) $source),
			italika_newsletter_get_ip(),
			$now,
			$now
		));

		return [
			'subscriber' => italika_newsletter_get_by_email($email),
			'created' => true,
			'reactivated' => false,
		];
	}
}

if (!function_exists('italika_newsletter_set_status')) {
	function italika_newsletter_set_status($id, $status)
	{
		global $wpdb;

		$id = (int) $id;
		$status = $status === 'active' ? 'active' : 'unsubscribed';

		if ($id <= 0) {
			return false;
		}

		if ($status === 'active') {
			return (bool) $wpdb->query($wpdb->prepare(
				'UPDATE ' . italika_newsletter_table() . ' SET status = %s, updated_at = %s, unsubscribed_at = NULL WHERE id = %d',
				'active',
				current_time('mysql'),
				$id
			));
		}

		return (bool) $wpdb->query($wpdb->prepare(
			'UPDATE ' . italika_newsletter_table() . ' SET status = %s, updated_at = %s, unsubscribed_at = %s WHERE id = %d',
			'unsubscribed',
			current_time('mysql'),
			current_time('mysql'),
			$id
		));
	}
}

if (!function_exists('italika_newsletter_unsubscribe_by_token')) {
	function italika_newsletter_unsubscribe_by_token($token)
	{
		$subscriber = italika_newsletter_get_by_token($token);

		if (!$subscriber) {
			return false;
		}

		italika_newsletter_set_status((int) $subscriber->id, 'unsubscribed');

		return true;
	}
}

if (!function_exists('italika_newsletter_unsubscribe_url')) {
	function italika_newsletter_unsubscribe_url($subscriber)
	{
		$token = is_object($subscriber) && isset($subscriber->token) ? $subscriber->token : '';

		return add_query_arg([
			'italika_unsubscribe' => '1',
			'token' => rawurlencode($token),
		], home_url('/'));
	}
}

if (!function_exists('italika_newsletter_mail_wrap')) {
	function italika_newsletter_mail_wrap($title, $body_html, $subscriber)
	{
		$site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
		$unsubscribe_url = is_object($subscriber) ? italika_newsletter_unsubscribe_url($subscriber) : '';
		$logo_url = get_theme_file_uri('/assets/static/icons/italika-logo-fot.svg');

		return '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>' .
			'<body style="margin:0;padding:0;background:#f5ebd6;color:#2b2418;font-family:Inter,Arial,sans-serif;">' .
			'<div style="display:none;max-height:0;overflow:hidden;">' . esc_html($title) . '</div>' .
			'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5ebd6;padding:28px 12px;"><tr><td align="center">' .
			'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fff;border:1px solid #ddd1bb;border-radius:8px;overflow:hidden;">' .
			'<tr><td style="background:#24311c;padding:24px 28px;color:#fff;">' .
			'<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" height="44" style="display:block;margin:0 0 18px;max-width:180px;height:44px;">' .
			'<div style="font-size:14px;line-height:1.5;color:#ffffffcc;">Рассылка Italika</div>' .
			'<h1 style="margin:6px 0 0;font-size:28px;line-height:1.15;color:#fff;">' . esc_html($title) . '</h1>' .
			'</td></tr>' .
			'<tr><td style="padding:28px;">' . $body_html . '</td></tr>' .
			'<tr><td style="padding:0 28px 28px;">' .
			($unsubscribe_url ? '<a href="' . esc_url($unsubscribe_url) . '" style="display:inline-block;background:#a12b2f;color:#fff;text-decoration:none;border-radius:6px;padding:12px 18px;font-weight:700;">Отписаться</a>' : '') .
			'<p style="margin:' . ($unsubscribe_url ? '16px' : '0') . ' 0 0;color:#6b5b46;font-size:13px;line-height:1.5;">' . ($unsubscribe_url ? 'Вы получили это письмо, потому что подписались на новости и рецепты сайта ' . esc_html($site_name) . '.' : 'Служебное уведомление сайта ' . esc_html($site_name) . '.') . '</p>' .
			'</td></tr>' .
			'</table>' .
			'</td></tr></table>' .
			'</body></html>';
	}
}

if (!function_exists('italika_newsletter_send_mail')) {
	function italika_newsletter_send_mail($subscriber, $subject, $body_html)
	{
		if (!is_object($subscriber) || empty($subscriber->email) || $subscriber->status !== 'active') {
			return false;
		}

		$headers = ['Content-Type: text/html; charset=UTF-8'];
		$message = italika_newsletter_mail_wrap($subject, $body_html, $subscriber);

		return wp_mail($subscriber->email, $subject, $message, $headers);
	}
}

if (!function_exists('italika_newsletter_send_welcome')) {
	function italika_newsletter_send_welcome($subscriber)
	{
		$body = '<p style="margin:0 0 14px;font-size:17px;line-height:1.6;">Вы подписались на новости и рецепты Italika.</p>' .
			'<p style="margin:0;color:#6b5b46;font-size:15px;line-height:1.6;">Теперь мы будем присылать вам новые публикации сайта: полезные материалы, рецепты и идеи для кондитерских задач.</p>';

		return italika_newsletter_send_mail($subscriber, 'Вы подписались на рассылку Italika', $body);
	}
}

if (!function_exists('italika_newsletter_send_admin_notice')) {
	function italika_newsletter_send_admin_notice($subscriber, $event_label = 'Новая подписка')
	{
		if (!is_object($subscriber) || empty($subscriber->email)) {
			return false;
		}

		$admin_email = get_option('admin_email');

		if (!is_email($admin_email)) {
			return false;
		}

		$subject = $event_label . ' на рассылку Italika';
		$body = '<p style="margin:0 0 10px;color:#a12b2f;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">' . esc_html($event_label) . '</p>' .
			'<h2 style="margin:0 0 12px;color:#2b2418;font-size:24px;line-height:1.25;">' . esc_html($subscriber->email) . '</h2>' .
			'<p style="margin:0;color:#6b5b46;font-size:15px;line-height:1.6;">Источник: ' . esc_html($subscriber->source ?: 'site') . '. Дата: ' . esc_html($subscriber->updated_at ?: current_time('mysql')) . '.</p>';

		return wp_mail(
			$admin_email,
			$subject,
			italika_newsletter_mail_wrap($subject, $body, null),
			['Content-Type: text/html; charset=UTF-8']
		);
	}
}

if (!function_exists('italika_newsletter_get_active_subscribers')) {
	function italika_newsletter_get_active_subscribers()
	{
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM " . italika_newsletter_table() . " WHERE status = 'active' ORDER BY id ASC"
		);
	}
}

if (!function_exists('italika_newsletter_post_type_label')) {
	function italika_newsletter_post_type_label($post_type)
	{
		return $post_type === 'recipe' ? 'Новый рецепт' : 'Новая публикация';
	}
}

if (!function_exists('italika_newsletter_send_post')) {
	function italika_newsletter_send_post($post_id)
	{
		$post = get_post($post_id);

		if (!$post || !in_array($post->post_type, ['post', 'recipe'], true) || get_post_meta($post_id, '_italika_newsletter_sent', true)) {
			return;
		}

		$subscribers = italika_newsletter_get_active_subscribers();

		if (!$subscribers) {
			update_post_meta($post_id, '_italika_newsletter_sent', current_time('mysql'));
			return;
		}

		$title = get_the_title($post_id);
		$url = get_permalink($post_id);
		$label = italika_newsletter_post_type_label($post->post_type);
		$excerpt = has_excerpt($post_id)
			? get_the_excerpt($post_id)
			: wp_trim_words(wp_strip_all_tags((string) $post->post_content), 28);
		$subject = $label . ': ' . wp_specialchars_decode($title, ENT_QUOTES);

		foreach ($subscribers as $subscriber) {
			$body = '<p style="margin:0 0 10px;color:#a12b2f;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">' . esc_html($label) . '</p>' .
				'<h2 style="margin:0 0 12px;color:#2b2418;font-size:24px;line-height:1.25;">' . esc_html($title) . '</h2>' .
				'<p style="margin:0 0 22px;color:#6b5b46;font-size:16px;line-height:1.6;">' . esc_html($excerpt) . '</p>' .
				'<a href="' . esc_url($url) . '" style="display:inline-block;background:#2f6a2b;color:#fff;text-decoration:none;border-radius:6px;padding:13px 20px;font-weight:700;">Открыть материал</a>';

			italika_newsletter_send_mail($subscriber, $subject, $body);
		}

		update_post_meta($post_id, '_italika_newsletter_sent', current_time('mysql'));
	}
}

add_action('transition_post_status', function ($new_status, $old_status, $post) {
	if ($new_status !== 'publish' || $old_status === 'publish' || !$post || wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) {
		return;
	}

	if (!in_array($post->post_type, ['post', 'recipe'], true)) {
		return;
	}

	italika_newsletter_send_post((int) $post->ID);
}, 10, 3);

if (!function_exists('italika_newsletter_ajax_subscribe')) {
	function italika_newsletter_ajax_subscribe()
	{
		check_ajax_referer('italika_newsletter_nonce', 'nonce');

		if (function_exists('italika_spam_validate_form')) {
			$spam_check = italika_spam_validate_form('newsletter', [
				'min_delay' => 2,
				'max_age' => DAY_IN_SECONDS,
				'rate_limit_action' => 'newsletter',
				'rate_limit_limit' => 4,
				'rate_limit_window' => 1800,
				'message' => 'Проверка подписки не пройдена. Обновите страницу и попробуйте еще раз.',
			]);

			if (is_wp_error($spam_check)) {
				wp_send_json_error(['message' => $spam_check->get_error_message()], 400);
			}
		}

		$email = isset($_POST['email']) ? wp_unslash($_POST['email']) : '';
		$result = italika_newsletter_upsert($email, 'subscribe-modal');

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()], 400);
		}

		if (!empty($result['created']) || !empty($result['reactivated'])) {
			italika_newsletter_send_welcome($result['subscriber']);
			italika_newsletter_send_admin_notice($result['subscriber'], !empty($result['reactivated']) ? 'Подписка возобновлена' : 'Новая подписка');
		}

		$message = !empty($result['reactivated'])
			? 'Подписка снова активна. Мы отправили письмо с подтверждением.'
			: 'Вы подписались. Мы отправили письмо с подтверждением.';

		if (empty($result['created']) && empty($result['reactivated'])) {
			$message = 'Этот email уже подписан на новости и рецепты.';
		}

		wp_send_json_success(['message' => $message]);
	}
}

add_action('wp_ajax_italika_newsletter_subscribe', 'italika_newsletter_ajax_subscribe');
add_action('wp_ajax_nopriv_italika_newsletter_subscribe', 'italika_newsletter_ajax_subscribe');

add_action('init', function () {
	if (empty($_GET['italika_unsubscribe']) || empty($_GET['token'])) {
		return;
	}

	$token = sanitize_text_field(wp_unslash($_GET['token']));
	$ok = italika_newsletter_unsubscribe_by_token($token);
	$title = $ok ? 'Вы отписались от рассылки' : 'Ссылка отписки не найдена';
	$text = $ok
		? 'Мы больше не будем отправлять новости и рецепты на этот email.'
		: 'Возможно, подписка уже отключена или ссылка устарела.';

	wp_die(
		'<div style="font-family:Arial,sans-serif;max-width:620px;margin:50px auto;padding:28px;border:1px solid #ddd1bb;border-radius:8px;background:#fff;color:#2b2418;"><h1 style="margin-top:0;">' . esc_html($title) . '</h1><p style="font-size:16px;line-height:1.6;">' . esc_html($text) . '</p><p><a href="' . esc_url(home_url('/')) . '" style="color:#2f6a2b;font-weight:700;">Вернуться на сайт</a></p></div>',
		esc_html($title),
		['response' => 200]
	);
});

if (!function_exists('italika_newsletter_admin_page')) {
	function italika_newsletter_admin_page()
	{
		global $wpdb;

		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have permission to access this page.'));
		}

		$table = italika_newsletter_table();

		if (!empty($_GET['newsletter_action']) && !empty($_GET['subscriber_id'])) {
			$subscriber_id = (int) $_GET['subscriber_id'];
			$action = sanitize_key(wp_unslash($_GET['newsletter_action']));

			check_admin_referer('italika_newsletter_action_' . $subscriber_id);

			if ($action === 'unsubscribe') {
				italika_newsletter_set_status($subscriber_id, 'unsubscribed');
				echo '<div class="notice notice-success is-dismissible"><p>Подписчик отписан.</p></div>';
			} elseif ($action === 'activate') {
				italika_newsletter_set_status($subscriber_id, 'active');
				echo '<div class="notice notice-success is-dismissible"><p>Подписка активирована.</p></div>';
			}
		}

		$status = isset($_GET['status']) && $_GET['status'] === 'unsubscribed' ? 'unsubscribed' : 'active';
		$subscribers = $wpdb->get_results($wpdb->prepare(
			'SELECT * FROM ' . $table . ' WHERE status = %s ORDER BY updated_at DESC LIMIT 500',
			$status
		));
		$active_url = admin_url('admin.php?page=italika-newsletter&status=active');
		$unsubscribed_url = admin_url('admin.php?page=italika-newsletter&status=unsubscribed');
		?>
		<div class="wrap">
			<h1>Подписки на новости</h1>
			<p>Здесь можно посмотреть подписчиков и вручную отписать человека от рассылки сайта.</p>

			<p>
				<a class="button<?php echo $status === 'active' ? ' button-primary' : ''; ?>" href="<?php echo esc_url($active_url); ?>">Активные</a>
				<a class="button<?php echo $status === 'unsubscribed' ? ' button-primary' : ''; ?>" href="<?php echo esc_url($unsubscribed_url); ?>">Отписанные</a>
			</p>

			<table class="widefat striped">
				<thead>
					<tr>
						<th>Email</th>
						<th>Статус</th>
						<th>Источник</th>
						<th>Дата подписки</th>
						<th>Обновлено</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>
					<?php if ($subscribers) : ?>
						<?php foreach ($subscribers as $subscriber) : ?>
							<?php
							$action = $subscriber->status === 'active' ? 'unsubscribe' : 'activate';
							$action_text = $subscriber->status === 'active' ? 'Отписать' : 'Активировать';
							$action_url = wp_nonce_url(
								admin_url('admin.php?page=italika-newsletter&status=' . $status . '&newsletter_action=' . $action . '&subscriber_id=' . (int) $subscriber->id),
								'italika_newsletter_action_' . (int) $subscriber->id
							);
							?>
							<tr>
								<td><strong><?php echo esc_html($subscriber->email); ?></strong></td>
								<td><?php echo esc_html($subscriber->status === 'active' ? 'Активна' : 'Отписан'); ?></td>
								<td><?php echo esc_html($subscriber->source); ?></td>
								<td><?php echo esc_html($subscriber->created_at); ?></td>
								<td><?php echo esc_html($subscriber->updated_at); ?></td>
								<td><a class="button button-small" href="<?php echo esc_url($action_url); ?>"><?php echo esc_html($action_text); ?></a></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="6">Подписчиков в этом списке пока нет.</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}

add_action('admin_menu', function () {
	add_menu_page(
		'Подписки',
		'Подписки',
		'manage_options',
		'italika-newsletter',
		'italika_newsletter_admin_page',
		'dashicons-email-alt2',
		56
	);
});
