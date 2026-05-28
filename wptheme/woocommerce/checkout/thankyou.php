<?php
defined('ABSPATH') || exit;

$home_url = home_url('/');
?>

<section class="checkout-page checkout-page--success">
	<div class="container">
		<div class="checkout-page__head">
			<div class="checkout-page__intro">
				<span class="checkout-page__eyebrow">Готово</span>
				<h1 class="checkout-page__title">Заказ оформлен</h1>
				<p class="checkout-page__summary">
					Мы получили заказ. Менеджер свяжется с вами и подтвердит детали.
				</p>
			</div>
			<a class="checkout-page__back" href="<?php echo esc_url($home_url); ?>">На главную</a>
		</div>

		<div class="checkout-page__layout">
			<div class="checkout-page__main">
				<section class="checkout-page__panel">
					<h2 class="checkout-page__panel-title">Детали заказа</h2>

					<?php if ($order) : ?>
						<div class="checkout-page__items">
							<article class="checkout-page__item">
								<span class="checkout-page__item-name">Номер заказа</span>
								<strong class="checkout-page__item-price">№<?php echo esc_html($order->get_order_number()); ?></strong>
							</article>
							<article class="checkout-page__item">
								<span class="checkout-page__item-name">Дата</span>
								<strong class="checkout-page__item-price"><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></strong>
							</article>
							<article class="checkout-page__item">
								<span class="checkout-page__item-name">Получение</span>
								<strong class="checkout-page__item-price"><?php echo esc_html($order->get_shipping_method() ?: 'Не выбрано'); ?></strong>
							</article>
							<article class="checkout-page__item">
								<span class="checkout-page__item-name">Оплата</span>
								<strong class="checkout-page__item-price"><?php echo esc_html($order->get_payment_method_title() ?: 'Не выбрано'); ?></strong>
							</article>
						</div>
					<?php else : ?>
						<p class="checkout-page__manual-note">Заказ оформлен, но детали сейчас недоступны.</p>
					<?php endif; ?>
				</section>
			</div>

			<aside class="checkout-page__side" aria-label="Сумма заказа">
				<div class="checkout-page__summary-card">
					<h2 class="checkout-page__side-title">Итого</h2>

					<?php if ($order) : ?>
						<div class="checkout-page__items">
							<?php foreach ($order->get_items() as $item) : ?>
								<article class="checkout-page__item">
									<span class="checkout-page__item-name"><?php echo esc_html($item->get_name()); ?></span>
									<span class="checkout-page__item-meta"><?php echo esc_html($item->get_quantity()); ?> x <?php echo esc_html(italika_wc_format_price_plain($order->get_item_total($item, false))); ?></span>
									<strong class="checkout-page__item-price"><?php echo esc_html(italika_wc_format_price_plain($item->get_total())); ?></strong>
								</article>
							<?php endforeach; ?>
						</div>

						<dl class="checkout-page__totals">
							<div class="checkout-page__total-row">
								<dt>Товары</dt>
								<dd><?php echo esc_html(italika_wc_format_price_plain($order->get_subtotal())); ?></dd>
							</div>
							<div class="checkout-page__total-row">
								<dt>Получение</dt>
								<dd><?php echo esc_html($order->get_shipping_method() ?: 'Не выбрано'); ?></dd>
							</div>
							<div class="checkout-page__total-row checkout-page__total-row--grand">
								<dt>К оплате</dt>
								<dd><?php echo esc_html(italika_wc_format_price_plain($order->get_total())); ?></dd>
							</div>
						</dl>
					<?php endif; ?>

					<a class="checkout-page__submit" href="<?php echo esc_url($home_url); ?>">На главную</a>
					<p class="checkout-page__hint">Спасибо за заказ.</p>
				</div>
			</aside>
		</div>
	</div>
</section>
