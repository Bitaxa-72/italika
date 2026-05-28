<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_wc_format_price_plain')) {
	function italika_wc_format_price_plain($amount) {
		return wp_strip_all_tags(wc_price((float) $amount));
	}
}

if (!function_exists('italika_wc_get_shop_url')) {
	function italika_wc_get_shop_url() {
		if (function_exists('wc_get_page_permalink')) {
			$url = wc_get_page_permalink('shop');

			if ($url) {
				return $url;
			}
		}

		return home_url('/catalog/');
	}
}

if (!function_exists('italika_wc_force_russian_customer_location')) {
	function italika_wc_force_russian_customer_location() {
		if (!function_exists('WC') || !WC()->customer) {
			return;
		}

		if (method_exists(WC()->customer, 'set_billing_country')) {
			WC()->customer->set_billing_country('RU');
		}

		if (method_exists(WC()->customer, 'set_shipping_country')) {
			WC()->customer->set_shipping_country('RU');
		}

		if (method_exists(WC()->customer, 'set_country')) {
			WC()->customer->set_country('RU');
		}

		if (method_exists(WC()->customer, 'set_shipping_location')) {
			WC()->customer->set_shipping_location('RU', '', '', '');
		}
	}
}

add_filter('woocommerce_customer_default_location', function () {
	return 'RU';
});

add_filter('woocommerce_checkout_get_value', function ($value, $input) {
	if (in_array($input, ['billing_country', 'shipping_country'], true)) {
		return 'RU';
	}

	return $value;
}, 10, 2);

add_filter('woocommerce_checkout_posted_data', function ($data) {
	$data['billing_country'] = 'RU';
	$data['shipping_country'] = 'RU';

	if (function_exists('italika_wc_is_delivery_checkout_data') && !italika_wc_is_delivery_checkout_data($data)) {
		$data['billing_city'] = '';
		$data['billing_address_1'] = '';
	}

	return $data;
});

if (!function_exists('italika_wc_is_delivery_checkout_data')) {
	function italika_wc_is_delivery_checkout_data($data = []) {
		$shipping_methods = [];

		if (isset($data['shipping_method'])) {
			$shipping_methods = (array) $data['shipping_method'];
		} elseif (isset($_POST['shipping_method'])) {
			$shipping_methods = wc_clean(wp_unslash((array) $_POST['shipping_method']));
		}

		$selected_shipping = reset($shipping_methods);

		if (!$selected_shipping && function_exists('WC') && WC()->session) {
			$chosen_methods = (array) WC()->session->get('chosen_shipping_methods', []);
			$selected_shipping = reset($chosen_methods);
		}

		if (!$selected_shipping) {
			return false;
		}

		if (strpos((string) $selected_shipping, 'italika_delivery') === 0) {
			return true;
		}

		if (!function_exists('WC') || !WC()->shipping()) {
			return false;
		}

		foreach (WC()->shipping()->get_packages() as $package) {
			$rates = $package['rates'] ?? [];

			if (isset($rates[$selected_shipping]) && $rates[$selected_shipping]->get_method_id() === 'italika_delivery') {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('italika_wc_customer_type_options')) {
	function italika_wc_customer_type_options() {
		return [
			'individual' => 'Физ. лицо',
			'legal_entity' => 'Юр. лицо',
		];
	}
}

if (!function_exists('italika_wc_get_customer_type_label')) {
	function italika_wc_get_customer_type_label($value) {
		$options = italika_wc_customer_type_options();
		$value = isset($options[$value]) ? $value : 'individual';

		return $options[$value];
	}
}

if (!function_exists('italika_wc_get_customer_meta_keys')) {
	function italika_wc_get_customer_meta_keys() {
		return [
			'italika_customer_type' => '_italika_customer_type',
			'italika_company_name' => '_italika_company_name',
			'italika_company_inn' => '_italika_company_inn',
			'italika_bonus_card_number' => '_italika_bonus_card_number',
		];
	}
}

if (!function_exists('italika_wc_get_posted_customer_type')) {
	function italika_wc_get_posted_customer_type($source = null) {
		$value = '';

		if (is_array($source) && isset($source['italika_customer_type'])) {
			$value = sanitize_key((string) $source['italika_customer_type']);
		} elseif (isset($_POST['italika_customer_type'])) {
			$value = sanitize_key(wp_unslash((string) $_POST['italika_customer_type']));
		}

		return isset(italika_wc_customer_type_options()[$value]) ? $value : 'individual';
	}
}

if (!function_exists('italika_wc_get_posted_customer_details')) {
	function italika_wc_get_posted_customer_details($source = null) {
		$company_name = '';
		$company_inn = '';
		$bonus_card_number = '';

		if (is_array($source)) {
			$company_name = isset($source['italika_company_name']) ? sanitize_text_field((string) $source['italika_company_name']) : '';
			$company_inn = isset($source['italika_company_inn']) ? sanitize_text_field((string) $source['italika_company_inn']) : '';
			$bonus_card_number = isset($source['italika_bonus_card_number']) ? sanitize_text_field((string) $source['italika_bonus_card_number']) : '';
		}

		if ($company_name === '' && isset($_POST['italika_company_name'])) {
			$company_name = sanitize_text_field(wp_unslash((string) $_POST['italika_company_name']));
		}

		if ($company_inn === '' && isset($_POST['italika_company_inn'])) {
			$company_inn = sanitize_text_field(wp_unslash((string) $_POST['italika_company_inn']));
		}

		if ($bonus_card_number === '' && isset($_POST['italika_bonus_card_number'])) {
			$bonus_card_number = sanitize_text_field(wp_unslash((string) $_POST['italika_bonus_card_number']));
		}

		return [
			'customer_type' => italika_wc_get_posted_customer_type($source),
			'company_name' => $company_name,
			'company_inn' => $company_inn,
			'bonus_card_number' => $bonus_card_number,
		];
	}
}

if (!function_exists('italika_wc_get_order_customer_type')) {
	function italika_wc_get_order_customer_type(WC_Order $order) {
		$customer_type = sanitize_key((string) $order->get_meta('_italika_customer_type', true));

		if (!isset(italika_wc_customer_type_options()[$customer_type])) {
			$customer_type = 'individual';
		}

		return $customer_type;
	}
}

if (!function_exists('italika_wc_get_order_customer_details')) {
	function italika_wc_get_order_customer_details(WC_Order $order) {
		return [
			'customer_type' => italika_wc_get_order_customer_type($order),
			'company_name' => trim((string) $order->get_meta('_italika_company_name', true)),
			'company_inn' => trim((string) $order->get_meta('_italika_company_inn', true)),
			'bonus_card_number' => trim((string) $order->get_meta('_italika_bonus_card_number', true)),
		];
	}
}

if (!function_exists('italika_wc_get_last_customer_order')) {
	function italika_wc_get_last_customer_order($customer_id) {
		static $orders = [];

		$customer_id = (int) $customer_id;

		if ($customer_id < 1) {
			return null;
		}

		if (array_key_exists($customer_id, $orders)) {
			return $orders[$customer_id];
		}

		if (!function_exists('wc_get_orders')) {
			$orders[$customer_id] = null;
			return null;
		}

		$order_ids = wc_get_orders([
			'customer_id' => $customer_id,
			'limit' => 1,
			'orderby' => 'date',
			'order' => 'DESC',
			'return' => 'ids',
			'status' => array_keys(wc_get_order_statuses()),
		]);

		$orders[$customer_id] = !empty($order_ids) ? wc_get_order((int) $order_ids[0]) : null;

		return $orders[$customer_id];
	}
}

if (!function_exists('italika_wc_prefill_checkout_value')) {
	function italika_wc_prefill_checkout_value($value, $input) {
		if ($value !== null && $value !== '') {
			return $value;
		}

		$allowed_fields = array_merge(
			['billing_first_name', 'billing_last_name', 'billing_phone'],
			array_keys(italika_wc_get_customer_meta_keys())
		);

		if (!is_user_logged_in() || !in_array($input, $allowed_fields, true)) {
			return $value;
		}

		$user_id = get_current_user_id();

		if ($user_id < 1) {
			return $value;
		}

		$user_meta_value = get_user_meta($user_id, $input, true);

		if ($user_meta_value !== '') {
			return $user_meta_value;
		}

		$order = italika_wc_get_last_customer_order($user_id);

		if (!$order) {
			return $value;
		}

		switch ($input) {
			case 'billing_first_name':
				return $order->get_billing_first_name();
			case 'billing_last_name':
				return $order->get_billing_last_name();
			case 'billing_phone':
				return $order->get_billing_phone();
			default:
				$order_meta_key = italika_wc_get_customer_meta_keys()[$input] ?? '';
				return $order_meta_key ? $order->get_meta($order_meta_key, true) : $value;
		}
	}
}
add_filter('woocommerce_checkout_get_value', 'italika_wc_prefill_checkout_value', 20, 2);

if (!function_exists('italika_wc_checkout_field')) {
	function italika_wc_checkout_field($key, $field, $value = '', $extra_input_classes = []) {
		$type = $field['type'] ?? 'text';
		$label = $field['label'] ?? '';
		$placeholder = $field['placeholder'] ?? '';
		$autocomplete = $field['autocomplete'] ?? '';
		$required = !empty($field['required']);
		$classes = ['checkout-page__field'];
		$input_classes = array_merge((array) ($field['input_class'] ?? []), $extra_input_classes);

		if (in_array($key, ['billing_email', 'billing_address_1', 'order_comments'], true)) {
			$classes[] = 'checkout-page__field--wide';
		}

		printf('<label class="%s" for="%s">', esc_attr(implode(' ', array_unique($classes))), esc_attr($key));

		if ($label !== '') {
			printf('<span>%s</span>', esc_html($label));
		}

		if ($type === 'textarea') {
			printf(
				'<textarea id="%1$s" name="%1$s" rows="4" placeholder="%2$s"%3$s%4$s>%5$s</textarea>',
				esc_attr($key),
				esc_attr($placeholder),
				$required ? ' required' : '',
				$autocomplete ? ' autocomplete="' . esc_attr($autocomplete) . '"' : '',
				esc_textarea($value)
			);
		} else {
			printf(
				'<input id="%1$s" class="%2$s" type="%3$s" name="%1$s" value="%4$s" placeholder="%5$s"%6$s%7$s>',
				esc_attr($key),
				esc_attr(implode(' ', array_unique($input_classes))),
				esc_attr($type),
				esc_attr($value),
				esc_attr($placeholder),
				$required ? ' required' : '',
				$autocomplete ? ' autocomplete="' . esc_attr($autocomplete) . '"' : ''
			);
		}

		echo '</label>';
	}
}

if (!function_exists('italika_wc_cart_count_fragment')) {
	function italika_wc_cart_count_fragment($fragments) {
		$count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
		$fragments['.js-cart-count'] = sprintf(
			'<span class="icon-link__count js-cart-count"%s>%d</span>',
			$count > 0 ? '' : ' hidden',
			(int) $count
		);

		return $fragments;
	}
}

add_filter('woocommerce_add_to_cart_fragments', 'italika_wc_cart_count_fragment');

if (!function_exists('italika_wc_cart_item_data')) {
	function italika_wc_cart_item_data($cart_item_key, $cart_item) {
		$product = $cart_item['data'] ?? null;

		if (!$product || !$product->exists()) {
			return null;
		}

		$image_id = $product->get_image_id();
		$image = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail');
		$max_quantity = $product->get_max_purchase_quantity();
		$price = (float) wc_get_price_to_display($product);
		$regular_price = (float) $product->get_regular_price();
		$old_price = $regular_price > $price ? $regular_price : $price;

		$is_on_request = function_exists('italika_ecomcard_is_on_request') ? italika_ecomcard_is_on_request($product) : ((float) $price <= 0);
		$is_available = $is_on_request || $product->is_in_stock();

		return [
			'key' => $cart_item_key,
			'id' => (int) $product->get_id(),
			'title' => wp_strip_all_tags($product->get_name()),
			'image' => $image,
			'href' => $product->is_visible() ? $product->get_permalink($cart_item) : '#',
			'price' => $price,
			'oldPrice' => $old_price,
			'quantity' => (int) $cart_item['quantity'],
			'maxQuantity' => $max_quantity > 0 ? (int) $max_quantity : 999999,
			'isSelected' => true,
			'isAvailable' => $is_available,
			'sku' => $product->get_sku() ?: (string) $product->get_id(),
			'stockText' => $is_on_request ? 'Под заказ' : ($is_available ? 'В наличии' : 'В пути'),
		];
	}
}

if (!function_exists('italika_wc_cart_data')) {
	function italika_wc_cart_data() {
		$items = [];

		if (!function_exists('WC') || !WC()->cart) {
			return [
				'items' => [],
				'count' => 0,
			];
		}

		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			$item = italika_wc_cart_item_data($cart_item_key, $cart_item);

			if ($item) {
				$items[] = $item;
			}
		}

		return [
			'items' => $items,
			'count' => WC()->cart->get_cart_contents_count(),
		];
	}
}

if (!function_exists('italika_wc_ajax_cart_update')) {
	function italika_wc_ajax_cart_update() {
		check_ajax_referer('italika_wc_cart_nonce', 'nonce');

		if (function_exists('italika_spam_rate_limit')) {
			$rate_check = italika_spam_rate_limit('cart_update', 25, 60, 'Слишком много операций с корзиной за короткое время.');

			if (is_wp_error($rate_check)) {
				wp_send_json_error(['message' => $rate_check->get_error_message()], 429);
			}
		}

		if (!function_exists('WC') || !WC()->cart) {
			wp_send_json_error(['message' => 'Корзина недоступна.'], 400);
		}

		$cart_key = isset($_POST['cart_key']) ? sanitize_text_field(wp_unslash($_POST['cart_key'])) : '';
		$quantity = isset($_POST['quantity']) ? max(0, (int) $_POST['quantity']) : null;
		$cart_keys = isset($_POST['cart_keys']) ? array_map('sanitize_text_field', wp_unslash((array) $_POST['cart_keys'])) : [];
		$action_type = isset($_POST['cart_action_type']) ? sanitize_key(wp_unslash($_POST['cart_action_type'])) : '';

		if ($action_type === 'remove_selected' && $cart_keys) {
			foreach ($cart_keys as $key) {
				WC()->cart->remove_cart_item($key);
			}
		} elseif ($action_type === 'remove' && $cart_key) {
			WC()->cart->remove_cart_item($cart_key);
		} elseif ($action_type === 'quantity' && $cart_key && $quantity !== null) {
			WC()->cart->set_quantity($cart_key, $quantity, true);
		}

		WC()->cart->calculate_totals();

		wp_send_json_success(italika_wc_cart_data());
	}
}

add_action('wp_ajax_italika_wc_cart_update', 'italika_wc_ajax_cart_update');
add_action('wp_ajax_nopriv_italika_wc_cart_update', 'italika_wc_ajax_cart_update');

if (!function_exists('italika_wc_load_shipping_methods')) {
	function italika_wc_load_shipping_methods() {
		if (!class_exists('WC_Shipping_Method') && defined('WC_ABSPATH') && file_exists(WC_ABSPATH . 'includes/abstracts/abstract-wc-shipping-method.php')) {
			require_once WC_ABSPATH . 'includes/abstracts/abstract-wc-shipping-method.php';
		}

		if (!class_exists('WC_Shipping_Method') || class_exists('Italika_WC_Shipping_Method')) {
			return;
		}

		abstract class Italika_WC_Shipping_Method extends WC_Shipping_Method {
			protected $default_title = '';

			public function __construct($instance_id = 0) {
				$this->instance_id = absint($instance_id);
				$this->method_title = $this->default_title;
				$this->method_description = 'Способ получения заказа Italika.';
				$this->supports = ['shipping-zones', 'instance-settings'];
				$this->enabled = 'yes';
				$this->title = $this->default_title;

				$this->init();
			}

			public function init() {
				$this->init_form_fields();
				$this->init_settings();

				$this->enabled = $this->get_option('enabled', 'yes');
				$this->title = $this->get_option('title', $this->default_title);

				add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
			}

			public function init_form_fields() {
				$this->instance_form_fields = [
					'enabled' => [
						'title' => 'Включить',
						'type' => 'checkbox',
						'label' => 'Показывать этот способ получения',
						'default' => 'yes',
					],
					'title' => [
						'title' => 'Название',
						'type' => 'text',
						'default' => $this->default_title,
					],
				];
			}

			public function calculate_shipping($package = []) {
				if ($this->enabled !== 'yes') {
					return;
				}

				$this->add_rate([
					'id' => $this->get_rate_id(),
					'label' => $this->title,
					'cost' => 0,
					'package' => $package,
				]);
			}
		}

		class Italika_WC_Shipping_Pickup extends Italika_WC_Shipping_Method {
			public function __construct($instance_id = 0) {
				$this->id = 'italika_pickup';
				$this->default_title = 'Самовывоз';
				parent::__construct($instance_id);
			}
		}

		class Italika_WC_Shipping_Delivery extends Italika_WC_Shipping_Method {
			public function __construct($instance_id = 0) {
				$this->id = 'italika_delivery';
				$this->default_title = 'Доставка';
				parent::__construct($instance_id);
			}
		}
	}
}

add_filter('woocommerce_shipping_methods', function ($methods) {
	italika_wc_load_shipping_methods();

	if (class_exists('Italika_WC_Shipping_Pickup')) {
		$methods['italika_pickup'] = 'Italika_WC_Shipping_Pickup';
		$methods['italika_delivery'] = 'Italika_WC_Shipping_Delivery';
	}

	return $methods;
});

add_action('woocommerce_loaded', 'italika_wc_load_shipping_methods');
add_action('woocommerce_init', 'italika_wc_load_shipping_methods');

add_action('template_redirect', function () {
	if (!function_exists('is_cart') || !is_cart() || empty($_POST['italika_remove_selected']) || empty($_POST['italika_selected_cart_items'])) {
		return;
	}

	if (empty($_POST['woocommerce-cart-nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woocommerce-cart-nonce'])), 'woocommerce-cart')) {
		return;
	}

	if (!function_exists('WC') || !WC()->cart) {
		return;
	}

	$keys = array_map('sanitize_text_field', wp_unslash((array) $_POST['italika_selected_cart_items']));

	foreach ($keys as $cart_item_key) {
		WC()->cart->remove_cart_item($cart_item_key);
	}

	wp_safe_redirect(wc_get_cart_url());
	exit;
});

add_filter('woocommerce_checkout_fields', function ($fields) {
	$fields['billing']['billing_first_name']['label'] = 'Имя';
	$fields['billing']['billing_first_name']['placeholder'] = 'Как к вам обращаться';
	$fields['billing']['billing_first_name']['priority'] = 10;
	$fields['billing']['billing_first_name']['required'] = true;
	$fields['billing']['billing_first_name']['type'] = 'text';
	$fields['billing']['billing_first_name']['autocomplete'] = 'name';

	$fields['billing']['billing_last_name']['label'] = 'Фамилия';
	$fields['billing']['billing_last_name']['placeholder'] = 'Ваша фамилия';
	$fields['billing']['billing_last_name']['priority'] = 15;
	$fields['billing']['billing_last_name']['required'] = true;
	$fields['billing']['billing_last_name']['type'] = 'text';
	$fields['billing']['billing_last_name']['autocomplete'] = 'family-name';

	$fields['billing']['billing_phone']['label'] = 'Телефон';
	$fields['billing']['billing_phone']['placeholder'] = '+7';
	$fields['billing']['billing_phone']['priority'] = 20;
	$fields['billing']['billing_phone']['required'] = true;
	$fields['billing']['billing_phone']['type'] = 'tel';
	$fields['billing']['billing_phone']['autocomplete'] = 'tel';

	$fields['billing']['billing_email']['label'] = 'Email';
	$fields['billing']['billing_email']['placeholder'] = 'Для состава заказа';
	$fields['billing']['billing_email']['priority'] = 30;
	$fields['billing']['billing_email']['required'] = false;
	$fields['billing']['billing_email']['type'] = 'email';
	$fields['billing']['billing_email']['autocomplete'] = 'email';

	$fields['billing']['billing_city']['label'] = 'Город';
	$fields['billing']['billing_city']['placeholder'] = 'Город доставки';
	$fields['billing']['billing_city']['required'] = false;
	$fields['billing']['billing_city']['autocomplete'] = 'address-level2';

	$fields['billing']['billing_address_1']['label'] = 'Пункт выдачи';
	$fields['billing']['billing_address_1']['placeholder'] = 'Адрес или название пункта выдачи';
	$fields['billing']['billing_address_1']['required'] = false;
	$fields['billing']['billing_address_1']['autocomplete'] = 'street-address';

	unset(
		$fields['billing']['billing_company'],
		$fields['billing']['billing_country'],
		$fields['billing']['billing_state'],
		$fields['billing']['billing_address_2'],
		$fields['billing']['billing_postcode']
	);

	if (isset($fields['shipping'])) {
		foreach ($fields['shipping'] as $key => $field) {
			$fields['shipping'][$key]['required'] = false;
		}
	}

	$fields['order']['order_comments']['label'] = 'Комментарий к заказу';
	$fields['order']['order_comments']['placeholder'] = 'Удобное время связи, детали доставки или пожелания';

	return $fields;
});

add_filter('woocommerce_form_field_args', function ($args, $key) {
	if (strpos((string) $key, 'billing_') === 0 || $key === 'order_comments') {
		$args['class'] = ['checkout-page__field'];
		$args['input_class'] = ['checkout-page__input'];
		$args['label_class'] = ['checkout-page__label'];
	}

	if (in_array($key, ['billing_email', 'billing_address_1', 'order_comments'], true)) {
		$args['class'][] = 'checkout-page__field--wide';
	}

	return $args;
}, 10, 2);

add_filter('woocommerce_gateway_description', function ($description, $payment_id) {
	$descriptions = [
		'italika_cash_pickup' => 'Менеджер подтвердит сумму заказа и условия.',
		'italika_card_pickup' => 'Оплата при получении после подтверждения.',
		'italika_payment_link' => 'Менеджер отправит ссылку после проверки заказа.',
	];

	return $descriptions[$payment_id] ?? $description;
}, 10, 2);

add_filter('woocommerce_available_payment_gateways', function ($gateways) {
	if (isset($gateways['italika_cash_pickup'])) {
		$gateways['italika_cash_pickup']->title = 'Наличными';
		$gateways['italika_cash_pickup']->description = 'Менеджер подтвердит сумму заказа и условия.';
	}

	if (isset($gateways['italika_card_pickup'])) {
		$gateways['italika_card_pickup']->title = 'Картой при получении';
		$gateways['italika_card_pickup']->description = 'Оплата картой при получении после подтверждения.';
	}

	if (isset($gateways['italika_payment_link'])) {
		$gateways['italika_payment_link']->description = 'Менеджер отправит ссылку после проверки заказа.';
	}

	return $gateways;
});

add_filter('woocommerce_email_mobile_messaging_enabled', '__return_false');

add_action('woocommerce_after_checkout_validation', function ($data, $errors) {
	if (!italika_wc_is_delivery_checkout_data($data)) {
		if (method_exists($errors, 'remove')) {
			$errors->remove('billing_city_required');
			$errors->remove('billing_address_required');
			$errors->remove('billing_address_1_required');
			$errors->remove('shipping_address_1_required');
			$errors->remove('shipping_city_required');
		}

		return;
	}

	if (empty($data['billing_city'])) {
		$errors->add('billing_city_required', 'Укажите город доставки.');
	}

	if (empty($data['billing_address_1'])) {
		$errors->add('billing_address_required', 'Укажите адрес доставки.');
	}
}, 10, 2);

add_action('woocommerce_after_checkout_validation', function ($data, $errors) {
	$first_name = isset($data['billing_first_name']) ? trim((string) $data['billing_first_name']) : '';
	$last_name = isset($data['billing_last_name']) ? trim((string) $data['billing_last_name']) : '';
	$phone = isset($data['billing_phone']) ? trim((string) $data['billing_phone']) : '';
	$customer_details = italika_wc_get_posted_customer_details($data);
	$customer_type = $customer_details['customer_type'];
	$company_name = $customer_details['company_name'];
	$company_inn = $customer_details['company_inn'];

	if ($first_name === '') {
		$errors->add('billing_first_name_error', 'Укажите имя.');
	}

	if ($last_name === '') {
		$errors->add('billing_last_name_error', 'Укажите фамилию.');
	}

	if ($phone === '') {
		$errors->add('billing_phone_error', 'Укажите телефон.');
	} elseif (function_exists('italika_wc_normalize_phone') && strlen(italika_wc_normalize_phone($phone)) < 7) {
		$errors->add('billing_phone_error', 'Укажите корректный телефон.');
	}

	if ($customer_type === 'legal_entity') {
		if ($company_name === '') {
			$errors->add('italika_company_name_error', 'Укажите наименование компании.');
		}

		if ($company_inn === '') {
			$errors->add('italika_company_inn_error', 'Укажите ИНН.');
		}
	}
}, 20, 2);

add_action('woocommerce_checkout_create_order', function ($order, $data) {
	$customer_details = italika_wc_get_posted_customer_details($data);

	$order->update_meta_data('_italika_customer_type', $customer_details['customer_type']);
	$order->update_meta_data('_italika_bonus_card_number', $customer_details['bonus_card_number']);

	if ($customer_details['customer_type'] === 'legal_entity') {
		$order->update_meta_data('_italika_company_name', $customer_details['company_name']);
		$order->update_meta_data('_italika_company_inn', $customer_details['company_inn']);
	} else {
		$order->delete_meta_data('_italika_company_name');
		$order->delete_meta_data('_italika_company_inn');
	}
}, 10, 2);

add_action('woocommerce_checkout_update_order_meta', function ($order_id, $data) {
	$user_id = get_current_user_id();
	$customer_details = italika_wc_get_posted_customer_details($data);

	if ($user_id < 1) {
		return;
	}

	$first_name = isset($data['billing_first_name']) ? sanitize_text_field((string) $data['billing_first_name']) : '';
	$last_name = isset($data['billing_last_name']) ? sanitize_text_field((string) $data['billing_last_name']) : '';
	$phone = isset($data['billing_phone']) ? sanitize_text_field((string) $data['billing_phone']) : '';

	if ($first_name !== '') {
		update_user_meta($user_id, 'first_name', $first_name);
		update_user_meta($user_id, 'billing_first_name', $first_name);
	}

	if ($last_name !== '') {
		update_user_meta($user_id, 'last_name', $last_name);
		update_user_meta($user_id, 'billing_last_name', $last_name);
	}

	if ($phone !== '') {
		update_user_meta($user_id, 'billing_phone', $phone);

		if (function_exists('italika_wc_normalize_phone')) {
			update_user_meta($user_id, 'italika_normalized_phone', italika_wc_normalize_phone($phone));
		}
	}

	update_user_meta($user_id, 'italika_customer_type', $customer_details['customer_type']);
	update_user_meta($user_id, 'italika_bonus_card_number', $customer_details['bonus_card_number']);

	if ($customer_details['customer_type'] === 'legal_entity') {
		update_user_meta($user_id, 'italika_company_name', $customer_details['company_name']);
		update_user_meta($user_id, 'italika_company_inn', $customer_details['company_inn']);
	} else {
		delete_user_meta($user_id, 'italika_company_name');
		delete_user_meta($user_id, 'italika_company_inn');
	}
}, 10, 2);

add_filter('woocommerce_email_subject_new_order', function ($subject, $order) {
	if (!$order instanceof WC_Order) {
		return $subject;
	}

	$customer_type = italika_wc_get_order_customer_type($order);

	return '[' . italika_wc_get_customer_type_label($customer_type) . '] ' . $subject;
}, 10, 2);

add_action('woocommerce_email_after_order_table', function ($order, $sent_to_admin, $plain_text, $email) {
	if (!$sent_to_admin || !$order instanceof WC_Order) {
		return;
	}

	$customer_details = italika_wc_get_order_customer_details($order);
	$customer_type = $customer_details['customer_type'];
	$company_name = $customer_details['company_name'];
	$company_inn = $customer_details['company_inn'];
	$bonus_card_number = $customer_details['bonus_card_number'];
	$lines = [
		'Тип клиента: ' . italika_wc_get_customer_type_label($customer_type),
	];

	if ($customer_type === 'legal_entity' && $company_name !== '') {
		$lines[] = 'Компания: ' . $company_name;
	}

	if ($customer_type === 'legal_entity' && $company_inn !== '') {
		$lines[] = 'ИНН: ' . $company_inn;
	}

	if ($bonus_card_number !== '') {
		$lines[] = 'Бонусная карта: ' . $bonus_card_number;
	}

	if ($plain_text) {
		echo "\n" . implode("\n", array_map('wp_strip_all_tags', $lines)) . "\n";
		return;
	}

	echo '<div style="margin:16px 0 0;">';
	echo '<p style="margin:0 0 8px;"><strong>Дополнительно</strong></p>';

	foreach ($lines as $line) {
		echo '<p style="margin:0 0 6px;">' . esc_html($line) . '</p>';
	}

	echo '</div>';
}, 15, 4);

add_filter('woocommerce_payment_gateways', function ($gateways) {
	italika_wc_load_manual_gateways();

	if (class_exists('Italika_WC_Gateway_Cash_Pickup')) {
		$gateways[] = 'Italika_WC_Gateway_Cash_Pickup';
		$gateways[] = 'Italika_WC_Gateway_Card_Pickup';
		$gateways[] = 'Italika_WC_Gateway_Payment_Link';
	}

	return $gateways;
});

if (!function_exists('italika_wc_load_manual_gateways')) {
	function italika_wc_load_manual_gateways() {
		if (!class_exists('WC_Payment_Gateway') && defined('WC_ABSPATH') && file_exists(WC_ABSPATH . 'includes/abstracts/abstract-wc-payment-gateway.php')) {
			require_once WC_ABSPATH . 'includes/abstracts/abstract-wc-payment-gateway.php';
		}

		if (!class_exists('WC_Payment_Gateway') || class_exists('Italika_WC_Gateway_Manual')) {
			return;
		}

		abstract class Italika_WC_Gateway_Manual extends WC_Payment_Gateway {
			protected $default_title = '';
			protected $default_description = '';

			public function __construct() {
				$this->has_fields = false;
				$this->method_title = $this->default_title;
				$this->method_description = $this->default_description;
				$this->supports = ['products'];

				$this->init_form_fields();
				$this->init_settings();

				$this->enabled = 'yes';
				$this->title = $this->get_option('title', $this->default_title);
				$this->description = $this->get_option('description', $this->default_description);

				add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
			}

			public function is_available() {
				return 'yes' === $this->enabled && (!function_exists('WC') || !WC()->cart || WC()->cart->needs_payment());
			}

			public function init_form_fields() {
				$this->form_fields = [
					'enabled' => [
						'title' => 'Включить',
						'type' => 'checkbox',
						'label' => 'Показывать этот способ оплаты',
						'default' => 'yes',
					],
					'title' => [
						'title' => 'Название',
						'type' => 'text',
						'default' => $this->default_title,
					],
					'description' => [
						'title' => 'Описание',
						'type' => 'textarea',
						'default' => $this->default_description,
					],
				];
			}

			public function process_payment($order_id) {
				$order = wc_get_order($order_id);

				if (!$order) {
					return ['result' => 'failure'];
				}

				$order->update_status('on-hold', 'Заказ ожидает подтверждения менеджером.');
				wc_reduce_stock_levels($order_id);
				WC()->cart->empty_cart();

				return [
					'result' => 'success',
					'redirect' => $this->get_return_url($order),
				];
			}
		}

		class Italika_WC_Gateway_Cash_Pickup extends Italika_WC_Gateway_Manual {
			public function __construct() {
				$this->id = 'italika_cash_pickup';
				$this->default_title = 'Наличными';
				$this->default_description = 'Оплата наличными после подтверждения заказа менеджером.';
				$this->default_title = 'Картой при получении';
				parent::__construct();
			}
		}

		class Italika_WC_Gateway_Card_Pickup extends Italika_WC_Gateway_Manual {
			public function __construct() {
				$this->id = 'italika_card_pickup';
				$this->default_title = 'Картой при получении';
				$this->default_title = 'Картой на месте';
				$this->default_description = 'Оплата при получении после подтверждения.';
				parent::__construct();
			}
		}

		class Italika_WC_Gateway_Payment_Link extends Italika_WC_Gateway_Manual {
			public function __construct() {
				$this->id = 'italika_payment_link';
				$this->default_title = 'Ссылка для оплаты';
				$this->default_description = 'Менеджер отправит ссылку после проверки заказа.';
				parent::__construct();
			}
		}
	}
}

italika_wc_load_manual_gateways();
add_action('woocommerce_loaded', 'italika_wc_load_manual_gateways');
add_action('woocommerce_init', 'italika_wc_load_manual_gateways');

add_filter('woocommerce_email_styles', function ($css) {
	$css .= '
		#wrapper { background-color: #f5ebd6; }
		#template_container { border: 1px solid #ddd1bb; border-radius: 8px; overflow: hidden; box-shadow: none; }
		#template_header { background-color: #24311c; }
		#template_header h1 { color: #ffffff; font-weight: 800; }
		#body_content_inner { color: #2b2418; font-family: Inter, Arial, sans-serif; }
		#body_content_inner a { color: #2f6a2b; font-weight: 700; }
		#body_content_inner .button, .link { color: #2f6a2b; }
		.td { border-color: #ddd1bb; }
	';

	return $css;
});
