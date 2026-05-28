<?php
defined('ABSPATH') || exit;

$has_chosen_gateway = false;

if (!empty($available_gateways)) {
	foreach ($available_gateways as $gateway) {
		if (!empty($gateway->chosen)) {
			$has_chosen_gateway = true;
			break;
		}
	}

	if (!$has_chosen_gateway) {
		$first_gateway = reset($available_gateways);
		$first_gateway->chosen = true;
	}
}
?>

<?php if (WC()->cart->needs_payment()) : ?>
	<div class="checkout-page__payments">
		<?php if (!empty($available_gateways)) : ?>
			<?php foreach ($available_gateways as $gateway) : ?>
				<?php wc_get_template('checkout/payment-method.php', ['gateway' => $gateway]); ?>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="checkout-page__manual-note">Укажите данные заказа, чтобы увидеть доступные способы оплаты.</p>
		<?php endif; ?>
	</div>
<?php endif; ?>

<p class="checkout-page__manual-note js-checkout-payment-note">
	Оплата пройдет вручную после звонка или сообщения менеджера.
</p>
