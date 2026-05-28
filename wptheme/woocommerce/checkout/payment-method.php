<?php
defined('ABSPATH') || exit;
?>

<label class="checkout-page__option">
	<input
		class="js-checkout-payment"
		type="radio"
		name="payment_method"
		value="<?php echo esc_attr($gateway->id); ?>"
		<?php checked($gateway->chosen, true); ?>
		data-order_button_text="<?php echo esc_attr($gateway->order_button_text); ?>"
	/>
	<span>
		<strong><?php echo wp_kses_post($gateway->get_title()); ?></strong>
		<?php if ($gateway->get_description()) : ?>
			<small><?php echo wp_kses_post($gateway->get_description()); ?></small>
		<?php endif; ?>
	</span>
</label>
