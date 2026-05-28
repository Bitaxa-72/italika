<?php
defined('ABSPATH') || exit;

$customer_id = get_current_user_id();
$customer = get_userdata($customer_id);
$first_name = trim((string) get_user_meta($customer_id, 'first_name', true));
$last_name = trim((string) get_user_meta($customer_id, 'last_name', true));
$display_name = $first_name !== '' ? $first_name : ($customer ? $customer->display_name : '');
$full_name = trim($first_name . ' ' . $last_name);
$full_name = $full_name !== '' ? $full_name : $display_name;
$email = $customer ? $customer->user_email : '';
$endpoint = function_exists('WC') && WC()->query ? WC()->query->get_current_endpoint() : '';
$active_endpoint = $endpoint ? $endpoint : 'dashboard';

if (in_array($active_endpoint, ['view-order', 'order-pay'], true)) {
	$active_endpoint = 'orders';
}

$account_url = italika_wc_get_account_url();
$favorites_url = function_exists('italika_favorites_get_archive_url') ? italika_favorites_get_archive_url() : home_url('/favorites/');
$logout_url = function_exists('wc_logout_url') ? wc_logout_url($account_url) : wp_logout_url($account_url);
$initial = $display_name !== ''
	? (function_exists('mb_substr') ? mb_strtoupper(mb_substr($display_name, 0, 1)) : strtoupper(substr($display_name, 0, 1)))
	: 'I';

$nav_items = [
	'dashboard' => [
		'label' => 'Данные аккаунта',
		'url' => $account_url,
	],
	'orders' => [
		'label' => 'История заказов',
		'url' => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('orders') : $account_url,
	],
	'edit-account' => [
		'label' => 'Профиль и пароль',
		'url' => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('edit-account') : $account_url,
	],
];

$titles = [
	'dashboard' => 'Личный кабинет',
	'orders' => 'История заказов',
	'edit-account' => 'Профиль и пароль',
	'downloads' => 'Загрузки',
	'payment-methods' => 'Способы оплаты',
];

$content_title = isset($titles[$active_endpoint]) ? $titles[$active_endpoint] : 'Личный кабинет';
?>

<section class="lk-main">
	<div class="container">
		<div class="lk-main__head">
			<div class="lk-main__intro">
				<p class="lk-main__eyebrow">Личный кабинет</p>
				<h1 class="lk-main__title">Здравствуйте, <?php echo esc_html($display_name); ?></h1>
				<p class="lk-main__text">Проверьте данные аккаунта и историю покупок.</p>
			</div>

			<a class="lk-main__logout" href="<?php echo esc_url($logout_url); ?>">Выйти</a>
		</div>

		<div class="lk-main__layout">
			<aside class="lk-main__side" aria-label="Разделы личного кабинета">
				<div class="lk-main__user">
					<span class="lk-main__avatar" aria-hidden="true"><?php echo esc_html($initial); ?></span>
					<span class="lk-main__user-info">
						<strong><?php echo esc_html($full_name); ?></strong>
						<span><?php echo esc_html($email); ?></span>
					</span>
				</div>

				<nav class="lk-main__nav">
					<?php foreach ($nav_items as $key => $item) : ?>
						<a class="lk-main__nav-item lk-main__nav-link <?php echo $active_endpoint === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url($item['url']); ?>">
							<?php echo esc_html($item['label']); ?>
						</a>
					<?php endforeach; ?>

					<a class="lk-main__nav-item lk-main__nav-link" href="<?php echo esc_url($favorites_url); ?>">Избранное</a>
				</nav>
			</aside>

			<div class="lk-main__content">
				<section class="lk-main__panel is-active">
					<div class="lk-main__panel-head">
						<div>
							<h2 class="lk-main__panel-title"><?php echo esc_html($content_title); ?></h2>
							<p class="lk-main__panel-text">Данные синхронизируются с WooCommerce и используются при оформлении заказа.</p>
						</div>
					</div>

					<div class="italika-woocommerce-account-content">
						<?php
						if ($active_endpoint === 'dashboard') {
							$dashboard_template = locate_template('woocommerce/myaccount/dashboard.php');

							if ($dashboard_template !== '') {
								include $dashboard_template;
							}
						} else {
							do_action('woocommerce_account_content');
						}
						?>
					</div>
				</section>
			</div>
		</div>
	</div>
</section>
