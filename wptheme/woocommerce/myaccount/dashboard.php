<?php
defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$customer_id = get_current_user_id();
$orders_url = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('orders') : italika_wc_get_account_url();
$edit_account_url = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('edit-account') : italika_wc_get_account_url();
$favorites_url = function_exists('italika_favorites_get_archive_url') ? italika_favorites_get_archive_url() : home_url('/favorites/');
$catalog_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/');
$phone = get_user_meta($customer_id, 'billing_phone', true);
$favorites_count = function_exists('italika_favorites_get_count') ? italika_favorites_get_count($customer_id) : 0;
$orders = function_exists('wc_get_orders')
	? wc_get_orders([
		'customer_id' => $customer_id,
		'limit' => 3,
		'orderby' => 'date',
		'order' => 'DESC',
		'return' => 'objects',
	])
	: [];
?>

<div class="italika-account-dashboard">
	<div class="italika-account-dashboard__grid">
		<article class="italika-account-card italika-account-card--wide">
			<p class="italika-account-card__eyebrow">Профиль</p>
			<h3 class="italika-account-card__title"><?php echo esc_html($current_user->display_name); ?></h3>
			<div class="italika-account-card__meta">
				<span><?php echo esc_html($current_user->user_email); ?></span>
				<?php if ($phone !== '') : ?>
					<span><?php echo esc_html($phone); ?></span>
				<?php endif; ?>
			</div>
			<a class="lk-main__secondary" href="<?php echo esc_url($edit_account_url); ?>">Изменить профиль</a>
		</article>

		<article class="italika-account-card">
			<p class="italika-account-card__eyebrow">Избранное</p>
			<h3 class="italika-account-card__title"><?php echo (int) $favorites_count; ?></h3>
			<p class="italika-account-card__text">Товары, которые вы сохранили для следующих заказов.</p>
			<a class="lk-main__secondary" href="<?php echo esc_url($favorites_url); ?>">Открыть избранное</a>
		</article>
	</div>

	<section class="italika-account-card">
		<div class="italika-account-card__head">
			<div>
				<p class="italika-account-card__eyebrow">Заказы</p>
				<h3 class="italika-account-card__title">Последние покупки</h3>
			</div>
			<a class="italika-account-card__link" href="<?php echo esc_url($orders_url); ?>">Вся история</a>
		</div>

		<?php if (!empty($orders)) : ?>
			<div class="italika-account-orders">
				<?php foreach ($orders as $order) : ?>
					<?php
					$order_date = $order->get_date_created();
					$order_url = $order->get_view_order_url();
					?>
					<a class="italika-account-order" href="<?php echo esc_url($order_url); ?>">
						<span class="italika-account-order__number">№ <?php echo esc_html($order->get_order_number()); ?></span>
						<strong><?php echo $order_date ? esc_html(wc_format_datetime($order_date)) : ''; ?></strong>
						<span><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></span>
						<b><?php echo wp_kses_post($order->get_formatted_order_total()); ?></b>
					</a>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="italika-account-empty">
				<h4 class="italika-account-empty__title">Заказов пока нет</h4>
				<p class="italika-account-empty__text">Когда вы оформите первый заказ, он появится здесь.</p>
				<a class="lk-main__submit" href="<?php echo esc_url($catalog_url); ?>">Перейти в каталог</a>
			</div>
		<?php endif; ?>
	</section>
</div>
