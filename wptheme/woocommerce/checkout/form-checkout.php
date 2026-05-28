<?php
defined('ABSPATH') || exit;

if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
	echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', 'Необходимо войти в аккаунт, чтобы оформить заказ.'));
	return;
}

if (function_exists('italika_wc_force_russian_customer_location')) {
	italika_wc_force_russian_customer_location();
}

if (WC()->cart && WC()->cart->needs_shipping()) {
	WC()->cart->calculate_shipping();
}

$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
WC()->payment_gateways()->set_current_gateway($available_gateways);
$order_button_text = 'Отправить заказ';
$shipping_packages = WC()->shipping() ? WC()->shipping()->get_packages() : [];
$chosen_shipping_methods = WC()->session ? (array) WC()->session->get('chosen_shipping_methods', []) : [];
?>

<section class="checkout-page" data-checkout-endpoint="<?php echo esc_url(wc_get_checkout_url()); ?>">
	<div class="container">
		<div class="checkout-page__head">
			<div class="checkout-page__intro">
				<span class="checkout-page__eyebrow">Оформление</span>
				<h1 class="checkout-page__title">Данные заказа</h1>
				<p class="checkout-page__summary js-checkout-summary">
					Заполните данные и подтвердите заказ.
				</p>
			</div>
			<a class="checkout-page__back" href="<?php echo esc_url(wc_get_cart_url()); ?>">Вернуться в корзину</a>
		</div>

		<form
			name="checkout"
			class="checkout-page__layout js-checkout-form"
			method="post"
			action="<?php echo esc_url(wc_get_checkout_url()); ?>"
			enctype="multipart/form-data"
			novalidate
		>
			<div class="checkout-page__notices woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout" aria-live="polite">
				<?php wc_print_notices(); ?>
			</div>

			<div class="checkout-page__main">
				<section class="checkout-page__panel">
					<h2 class="checkout-page__panel-title">
						Покупатель
					</h2>
					<div class="checkout-page__fields">
						<label class="checkout-page__field">
							<span>Имя</span>
							<input
								class="js-checkout-required"
								type="text"
								name="billing_first_name"
								autocomplete="name"
								placeholder="Как к вам обращаться"
								value="<?php echo esc_attr($checkout->get_value('billing_first_name')); ?>"
								required
							/>
						</label>
						<label class="checkout-page__field">
							<span>Фамилия</span>
							<input
								class="js-checkout-required"
								type="text"
								name="billing_last_name"
								autocomplete="family-name"
								placeholder="Ваша фамилия"
								value="<?php echo esc_attr($checkout->get_value('billing_last_name')); ?>"
								required
							/>
						</label>
						<label class="checkout-page__field">
							<span>Телефон</span>
							<input
								class="js-checkout-required"
								type="tel"
								name="billing_phone"
								autocomplete="tel"
								placeholder="+7"
								value="<?php echo esc_attr($checkout->get_value('billing_phone')); ?>"
								required
							/>
						</label>
						<label class="checkout-page__field checkout-page__field--wide">
							<span>Email</span>
							<input
								type="email"
								name="billing_email"
								autocomplete="email"
								placeholder="Для состава заказа"
								value="<?php echo esc_attr($checkout->get_value('billing_email')); ?>"
							/>
						</label>
						<div class="checkout-page__address-type checkout-page__field checkout-page__field--wide">
							<span class="checkout-page__address-title">Тип покупателя</span>
							<label class="checkout-page__option">
								<input
									class="js-checkout-customer-type"
									type="radio"
									name="italika_customer_type"
									value="individual"
									<?php checked($checkout->get_value('italika_customer_type') ?: 'individual', 'individual'); ?>
								/>
								<span><strong>Физ. лицо</strong></span>
							</label>
							<label class="checkout-page__option">
								<input
									class="js-checkout-customer-type"
									type="radio"
									name="italika_customer_type"
									value="legal_entity"
									<?php checked($checkout->get_value('italika_customer_type'), 'legal_entity'); ?>
								/>
								<span><strong>Юр. лицо</strong></span>
							</label>
						</div>
						<div class="checkout-page__fields checkout-page__fields--address js-checkout-company-fields" hidden>
							<label class="checkout-page__field checkout-page__field--wide">
								<span>Наименование компании</span>
								<input
									class="js-checkout-company-required"
									type="text"
									name="italika_company_name"
									placeholder="ООО Ромашка"
									value="<?php echo esc_attr($checkout->get_value('italika_company_name')); ?>"
								/>
							</label>
							<label class="checkout-page__field">
								<span>ИНН</span>
								<input
									class="js-checkout-company-required"
									type="text"
									name="italika_company_inn"
									placeholder="ИНН компании"
									value="<?php echo esc_attr($checkout->get_value('italika_company_inn')); ?>"
								/>
							</label>
						</div>
						<label class="checkout-page__field checkout-page__field--wide">
							<span>Номер бонусной карты</span>
							<input
								type="text"
								name="italika_bonus_card_number"
								placeholder="Укажите номер бонусной карты"
								value="<?php echo esc_attr($checkout->get_value('italika_bonus_card_number')); ?>"
							/>
						</label>
					</div>
				</section>

				<section class="checkout-page__panel">
					<h2 class="checkout-page__panel-title">
						Получение
					</h2>
					<div class="checkout-page__delivery">
						<?php if (!empty($shipping_packages)) : ?>
							<?php foreach ($shipping_packages as $package_index => $package) : ?>
								<?php
								$rates = !empty($package['rates']) ? $package['rates'] : [];
								$chosen_rate_id = $chosen_shipping_methods[$package_index] ?? '';

								if (!$chosen_rate_id && !empty($rates)) {
									$first_rate = reset($rates);
									$chosen_rate_id = $first_rate ? $first_rate->get_id() : '';
								}
								?>
								<?php foreach ($rates as $rate) : ?>
									<?php
									$rate_id = $rate->get_id();
									$method_id = $rate->get_method_id();
									$is_delivery = $method_id === 'italika_delivery';
									?>
									<label class="checkout-page__option">
										<input
											class="js-checkout-delivery"
											type="radio"
											name="shipping_method[<?php echo esc_attr((string) $package_index); ?>]"
											value="<?php echo esc_attr($rate_id); ?>"
											data-method-id="<?php echo esc_attr($method_id); ?>"
											<?php checked($rate_id, $chosen_rate_id); ?>
										/>
										<span><strong><?php echo esc_html($rate->get_label()); ?></strong></span>
									</label>
								<?php endforeach; ?>
							<?php endforeach; ?>
						<?php else : ?>
							<p class="checkout-page__manual-note">Добавьте способы получения в настройках доставки WooCommerce.</p>
						<?php endif; ?>
					</div>

					<div class="checkout-page__fields checkout-page__fields--address js-checkout-address" hidden>
						<label class="checkout-page__field">
							<span>Город</span>
							<input
								class="js-checkout-address-required"
								type="text"
								name="billing_city"
								autocomplete="address-level2"
								placeholder="Город доставки"
								value="<?php echo esc_attr($checkout->get_value('billing_city')); ?>"
							/>
						</label>
						<label class="checkout-page__field checkout-page__field--wide">
							<span class="js-checkout-address-label">Адрес доставки</span>
							<input
								class="js-checkout-address-input js-checkout-address-required"
								type="text"
								name="billing_address_1"
								autocomplete="street-address"
								placeholder="Улица, дом, квартира или офис"
								value="<?php echo esc_attr($checkout->get_value('billing_address_1')); ?>"
							/>
						</label>
					</div>
				</section>

				<section class="checkout-page__panel">
					<h2 class="checkout-page__panel-title">Оплата</h2>
					<?php wc_get_template('checkout/payment.php', [
						'checkout' => $checkout,
						'available_gateways' => $available_gateways,
						'order_button_text' => $order_button_text,
					]); ?>
				</section>

				<section class="checkout-page__panel">
					<h2 class="checkout-page__panel-title">
						Комментарий
					</h2>
					<label class="checkout-page__field checkout-page__field--wide">
						<span>Комментарий к заказу</span>
						<textarea
							name="order_comments"
							rows="4"
							placeholder="Удобное время связи, детали доставки или пожелания"
						><?php echo esc_textarea($checkout->get_value('order_comments')); ?></textarea>
					</label>
				</section>
			</div>

			<aside class="checkout-page__side" aria-label="Состав заказа">
				<div class="checkout-page__summary-card">
					<h2 class="checkout-page__side-title">
						Ваш заказ
					</h2>
					<?php wc_get_template('checkout/review-order.php'); ?>
					<button
						class="checkout-page__submit js-checkout-submit"
						type="submit"
						name="woocommerce_checkout_place_order"
						id="place_order"
						value="<?php echo esc_attr($order_button_text); ?>"
						data-value="<?php echo esc_attr($order_button_text); ?>"
					>
						Отправить заказ
					</button>
					<p class="checkout-page__hint">
						После отправки менеджер свяжется с вами и вручную подтвердит оплату.
					</p>
					<p class="checkout-page__message js-checkout-message" aria-live="polite"></p>
				</div>
			</aside>

			<?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
			<?php if (function_exists('italika_spam_render_fields')) : ?>
				<?php italika_spam_render_fields('checkout'); ?>
			<?php endif; ?>
			<input type="hidden" name="billing_country" value="RU">
			<input type="hidden" name="shipping_country" value="RU">
		</form>
	</div>
</section>
