<?php
defined('ABSPATH') || exit;

$chosen_shipping_label = 'Не выбрано';

if (WC()->cart && WC()->cart->needs_shipping()) {
	$packages = WC()->shipping() ? WC()->shipping()->get_packages() : [];
	$chosen_methods = WC()->session ? (array) WC()->session->get('chosen_shipping_methods', []) : [];

	foreach ($packages as $package_index => $package) {
		$chosen_method = $chosen_methods[$package_index] ?? '';
		$rates = $package['rates'] ?? [];

		if (!$chosen_method && $rates) {
			$chosen_method = (string) array_key_first($rates);
		}

		if ($chosen_method && isset($rates[$chosen_method])) {
			$chosen_shipping_label = $rates[$chosen_method]->get_label();
			break;
		}
	}
}
?>

<div class="checkout-page__items js-checkout-items">
	<?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) : ?>
		<?php
		$product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);

		if (!$product || !$product->exists() || $cart_item['quantity'] <= 0 || !apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key)) {
			continue;
		}

		$product_price = (float) wc_get_price_to_display($product);
		$product_subtotal = $product_price * (int) $cart_item['quantity'];
		?>
		<article class="checkout-page__item">
			<span class="checkout-page__item-name"><?php echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key)); ?></span>
			<span class="checkout-page__item-meta"><?php echo esc_html($cart_item['quantity']); ?> x <?php echo esc_html(italika_wc_format_price_plain($product_price)); ?></span>
			<strong class="checkout-page__item-price"><?php echo esc_html(italika_wc_format_price_plain($product_subtotal)); ?></strong>
		</article>
	<?php endforeach; ?>
</div>

<dl class="checkout-page__totals">
	<div class="checkout-page__total-row">
		<dt>Товары</dt>
		<dd class="js-checkout-subtotal"><?php echo esc_html(italika_wc_format_price_plain(WC()->cart->get_subtotal())); ?></dd>
	</div>

	<?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
		<div class="checkout-page__total-row">
			<dt><?php echo esc_html(wc_cart_totals_coupon_label($coupon, false)); ?></dt>
			<dd>-<?php echo esc_html(italika_wc_format_price_plain(WC()->cart->get_coupon_discount_amount($code))); ?></dd>
		</div>
	<?php endforeach; ?>

	<div class="checkout-page__total-row">
		<dt>Получение</dt>
		<dd class="js-checkout-shipping"><?php echo esc_html($chosen_shipping_label); ?></dd>
	</div>

	<?php foreach (WC()->cart->get_fees() as $fee) : ?>
		<div class="checkout-page__total-row">
			<dt><?php echo esc_html($fee->name); ?></dt>
			<dd><?php echo esc_html(italika_wc_format_price_plain($fee->amount)); ?></dd>
		</div>
	<?php endforeach; ?>

	<div class="checkout-page__total-row checkout-page__total-row--grand">
		<dt>Итого</dt>
		<dd class="js-checkout-total"><?php echo esc_html(italika_wc_format_price_plain(WC()->cart->get_total('edit'))); ?></dd>
	</div>
</dl>
