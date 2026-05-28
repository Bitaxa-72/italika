<?php

add_action('after_setup_theme', function () {
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('woocommerce');
	add_theme_support('html5', [
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	]);

	register_nav_menus([
		'primary' => 'Primary Menu',
	]);
});

add_action('wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'empty-wp-theme-style',
		get_stylesheet_uri(),
		[],
		wp_get_theme()->get('Version')
	);

	$theme_version = wp_get_theme()->get('Version');
	$main_css = glob(get_theme_file_path('/assets/css/main-*.css'));
	$main_js = glob(get_theme_file_path('/assets/js/main-*.js'));
	$sale_js_path = get_theme_file_path('/assets/js/sale-products.js');
	$sale_js_uri = get_theme_file_uri('/assets/js/sale-products.js');
	$catalog_js_path = get_theme_file_path('/assets/js/catalog-page.js');
	$catalog_js_uri = get_theme_file_uri('/assets/js/catalog-page.js');
	$search_js_path = get_theme_file_path('/assets/js/search-page.js');
	$search_js_uri = get_theme_file_uri('/assets/js/search-page.js');
	$newsletter_js_path = get_theme_file_path('/assets/js/newsletter.js');
	$newsletter_js_uri = get_theme_file_uri('/assets/js/newsletter.js');
	$woocommerce_js_path = get_theme_file_path('/assets/js/woocommerce.js');
	$woocommerce_js_uri = get_theme_file_uri('/assets/js/woocommerce.js');
	$posts_archive_js_path = get_theme_file_path('/assets/js/posts-archive.js');
	$posts_archive_js_uri = get_theme_file_uri('/assets/js/posts-archive.js');

	if (!empty($main_css)) {
		$main_css_path = $main_css[0];

		wp_enqueue_style(
			'italika-main',
			get_theme_file_uri('/assets/css/' . basename($main_css_path)),
			['empty-wp-theme-style'],
			filemtime($main_css_path) ?: $theme_version
		);

		wp_add_inline_style(
			'italika-main',
			'
			.catalog-page__cat-item,
			.catalog-page__subcat-item{position:relative}
			.catalog-page__cat-toggle{display:none}
			.catalog-page__mobile-categories{display:none}
			.catalog-page__rail-toggle,
			.catalog-page__drawer-backdrop,
			.catalog-page__drawer-close{display:none}
			.catalog-page__cat-item:hover .catalog-page__subcats,
			.catalog-page__cat-item:focus-within .catalog-page__subcats{opacity:0!important;visibility:hidden!important;pointer-events:none!important;transform:translateX(-4px)!important}
			.catalog-page__cat-item:hover>.catalog-page__subcats,
			.catalog-page__cat-item:focus-within>.catalog-page__subcats,
			.catalog-page__subcat-item:hover>.catalog-page__subcats,
			.catalog-page__subcat-item:focus-within>.catalog-page__subcats{opacity:1!important;visibility:visible!important;pointer-events:auto!important;transform:translateX(0)!important;transition-delay:0s!important}
			.catalog-page__subcats .catalog-page__subcats{top:-12px!important;left:100%!important;z-index:20!important}
			@media (max-width:980px){
				body.is-catalog-drawer-open{overflow:hidden!important}
				.catalog-page{padding-bottom:48px}
				.catalog-page__head{grid-template-columns:1fr!important;gap:10px!important;margin-bottom:12px!important}
				.catalog-page__title{font-size:clamp(30px,5vw,42px)!important;line-height:1.04!important}
				.catalog-page__text{display:none!important}
				.catalog-page__toolbar{align-items:center!important}
				.catalog-page__toolbar-main{display:flex!important;align-items:center!important;gap:10px!important;min-width:0!important}
				.catalog-page__mobile-categories{min-height:42px!important;padding:0 18px!important;border:1px solid var(--accent)!important;border-radius:8px!important;background:var(--accent)!important;color:var(--color-white)!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;box-shadow:0 10px 22px var(--color-accent-a20)!important;font-size:14px!important;font-weight:900!important;line-height:1!important;white-space:nowrap!important;cursor:pointer!important;flex:0 0 auto!important}
				.catalog-page__mobile-categories:before{content:none!important;display:none!important}
				.catalog-page__mobile-categories:hover,.catalog-page__mobile-categories:focus-visible{border-color:var(--accent-strong)!important;background:var(--accent-strong)!important}
				.catalog-page__rail-toggle{position:fixed!important;right:16px!important;bottom:18px!important;z-index:70!important;min-width:118px!important;height:44px!important;padding:0 18px!important;border:1px solid var(--accent)!important;border-radius:999px!important;background:var(--accent)!important;color:var(--color-white)!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;box-shadow:0 14px 32px var(--color-accent-a28)!important;font-size:13px!important;font-weight:900!important;line-height:1!important;cursor:pointer!important;opacity:1!important}
				.catalog-page__rail-toggle:before{content:none!important;display:none!important}
				.catalog-page__rail-toggle:hover,.catalog-page__rail-toggle:focus-visible{border-color:var(--accent-strong)!important;background:var(--accent-strong)!important}
				.catalog-page__drawer-backdrop{position:fixed!important;inset:0!important;z-index:80!important;border:0!important;background:var(--color-text-a40)!important;opacity:0!important;visibility:hidden!important;pointer-events:none!important;display:block!important;transition:opacity var(--transition),visibility var(--transition)!important}
				.catalog-page.is-category-drawer-open .catalog-page__drawer-backdrop{opacity:1!important;visibility:visible!important;pointer-events:auto!important}
				.catalog-page__layout{grid-template-columns:1fr!important;gap:14px!important}
				.catalog-page__aside{position:fixed!important;inset:0 auto 0 0!important;top:0!important;z-index:90!important;width:min(380px,88vw)!important;min-width:0!important;padding:16px!important;background:var(--color-white-a96)!important;box-shadow:22px 0 46px var(--color-card-shadow-a10)!important;overflow:auto!important;transform:translateX(calc(-100% - 24px))!important;transition:transform .24s ease!important}
				.catalog-page.is-category-drawer-open .catalog-page__aside{transform:translateX(0)!important}
				.catalog-page__aside-head{position:sticky!important;top:-16px!important;z-index:3!important;margin:-16px -16px 12px!important;padding:16px!important;background:var(--color-white-a96)!important;border-bottom:1px solid var(--color-accent-a10)!important;display:flex!important;align-items:center!important;justify-content:space-between!important;gap:12px!important}
				.catalog-page__aside-title{margin:0!important;font-size:20px!important}
				.catalog-page__drawer-close{position:relative!important;width:42px!important;height:42px!important;border:1px solid var(--color-accent-a12)!important;border-radius:8px!important;background:var(--color-white-a100)!important;color:var(--text)!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;cursor:pointer!important}
				.catalog-page__drawer-close:before,.catalog-page__drawer-close:after{content:""!important;position:absolute!important;width:16px!important;height:2px!important;background:currentcolor!important;border-radius:2px!important}
				.catalog-page__drawer-close:before{transform:rotate(45deg)!important}
				.catalog-page__drawer-close:after{transform:rotate(-45deg)!important}
				.catalog-page__cat-list{display:grid!important;grid-template-columns:1fr!important;gap:8px!important;overflow:visible!important;padding:10px!important;scroll-snap-type:none!important}
				.catalog-page__cat-item,.catalog-page__subcat-item{flex:none!important;scroll-snap-align:none!important}
				.catalog-page__cat-link{min-height:46px!important;padding:0 48px 0 12px!important;font-size:13px!important;line-height:1.25!important}
				.catalog-page__cat-link:after,.catalog-page__subcat-link:after,.catalog-page__cat-item--has-children>.catalog-page__cat-link:after,.catalog-page__cat-item--has-children>.catalog-page__subcat-link:after{display:none!important}
				.catalog-page__cat-toggle{position:absolute!important;top:6px!important;right:6px!important;z-index:2!important;width:34px!important;height:34px!important;border:1px solid var(--color-accent-a12)!important;border-radius:8px!important;background:var(--color-white-a96)!important;color:var(--text)!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;cursor:pointer!important}
				.catalog-page__cat-toggle:before{content:""!important;width:8px!important;height:8px!important;border-top:2px solid currentcolor!important;border-right:2px solid currentcolor!important;transform:rotate(135deg) translate(-1px,1px)!important;transition:transform var(--transition)!important}
				.catalog-page__cat-item.is-open>.catalog-page__cat-toggle:before,.catalog-page__subcat-item.is-open>.catalog-page__cat-toggle:before{transform:rotate(-45deg) translate(-1px,1px)!important}
				.catalog-page__subcats{position:static!important;width:auto!important;min-height:0!important;margin-top:8px!important;padding:8px!important;box-shadow:none!important;opacity:1!important;visibility:visible!important;pointer-events:auto!important;transform:none!important;transition:none!important;display:none!important}
				.catalog-page__subcats .catalog-page__subcats{top:auto!important;left:auto!important;margin-left:12px!important}
				.catalog-page__cat-item:hover>.catalog-page__subcats,
				.catalog-page__cat-item:focus-within>.catalog-page__subcats,
				.catalog-page__subcat-item:hover>.catalog-page__subcats,
				.catalog-page__subcat-item:focus-within>.catalog-page__subcats{transform:none!important}
				.catalog-page__cat-item.is-open>.catalog-page__subcats,
				.catalog-page__subcat-item.is-open>.catalog-page__subcats{display:grid!important}
				.catalog-page__subcats a,.catalog-page__subcat-link{min-height:34px!important;padding:7px 10px!important;font-size:13px!important;line-height:1.25!important}
				.catalog-page__toolbar{margin-bottom:12px!important}
				.catalog-page__grid.sale-products__grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}
			}
			@media (max-width:767px){
				.catalog-page{padding:8px 0 40px}
				.catalog-page__toolbar-main{display:grid!important;grid-template-columns:1fr!important;gap:10px!important}
				.catalog-page__mobile-categories{width:max-content!important;max-width:100%!important}
				.catalog-page__aside{width:min(340px,90vw)!important}
				.catalog-page__toolbar{display:grid!important;align-items:start!important;gap:8px!important}
				.catalog-page__subtitle{font-size:24px!important}
				.catalog-page__grid.sale-products__grid{grid-template-columns:1fr!important}
			}'
		);
	}

	if (!empty($main_js)) {
		$main_js_path = $main_js[0];

		wp_enqueue_script(
			'italika-main',
			get_theme_file_uri('/assets/js/' . basename($main_js_path)),
			[],
			filemtime($main_js_path) ?: $theme_version,
			true
		);
	}

	if (file_exists($sale_js_path)) {
		wp_enqueue_script(
			'italika-sale-products',
			$sale_js_uri,
			[],
			filemtime($sale_js_path) ?: $theme_version,
			true
		);

		wp_localize_script('italika-sale-products', 'italikaSaleProductsData', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('italika_sale_products_nonce'),
		]);
	}

	if (file_exists($catalog_js_path)) {
		wp_enqueue_script(
			'italika-catalog-page',
			$catalog_js_uri,
			[],
			filemtime($catalog_js_path) ?: $theme_version,
			true
		);

		wp_localize_script('italika-catalog-page', 'italikaCatalogData', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('italika_catalog_nonce'),
		]);
	}

	if (file_exists($search_js_path)) {
		wp_enqueue_script(
			'italika-search-page',
			$search_js_uri,
			[],
			filemtime($search_js_path) ?: $theme_version,
			true
		);

		wp_localize_script('italika-search-page', 'italikaSearchData', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('italika_search_nonce'),
			'searchUrl' => home_url('/'),
		]);
	}

	if (file_exists($newsletter_js_path)) {
		wp_enqueue_script(
			'italika-newsletter',
			$newsletter_js_uri,
			[],
			filemtime($newsletter_js_path) ?: $theme_version,
			true
		);

		wp_localize_script('italika-newsletter', 'italikaNewsletterData', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('italika_newsletter_nonce'),
		]);
	}

	if (file_exists($woocommerce_js_path)) {
		$woocommerce_deps = ['jquery'];

		if (function_exists('WC') && wp_script_is('wc-add-to-cart', 'registered')) {
			wp_enqueue_script('wc-add-to-cart');
			$woocommerce_deps[] = 'wc-add-to-cart';
		}

		if (function_exists('WC') && wp_script_is('wc-cart-fragments', 'registered')) {
			wp_enqueue_script('wc-cart-fragments');
			$woocommerce_deps[] = 'wc-cart-fragments';
		}

		$is_order_received = function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received');

		if (function_exists('is_checkout') && is_checkout() && !$is_order_received && wp_script_is('wc-checkout', 'registered')) {
			wp_enqueue_script('wc-checkout');
			$woocommerce_deps[] = 'wc-checkout';
		}

		wp_enqueue_script(
			'italika-woocommerce',
			$woocommerce_js_uri,
			$woocommerce_deps,
			filemtime($woocommerce_js_path) ?: $theme_version,
			true
		);

		wp_localize_script('italika-woocommerce', 'italikaWooData', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'cartNonce' => wp_create_nonce('italika_wc_cart_nonce'),
			'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
			'checkoutUrl' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/'),
		]);
	}

	if (file_exists($posts_archive_js_path)) {
		wp_enqueue_script(
			'italika-posts-archive',
			$posts_archive_js_uri,
			[],
			filemtime($posts_archive_js_path) ?: $theme_version,
			true
		);

		wp_localize_script('italika-posts-archive', 'italikaPostsArchiveData', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('italika_posts_archive_nonce'),
			'labels' => [
				'more' => 'Загрузить еще',
				'loading' => 'Загрузка',
			],
		]);
	}
});

add_filter('script_loader_tag', function ($tag, $handle, $src) {
	if ('italika-main' !== $handle) {
		return $tag;
	}

	return sprintf(
		'<script type="module" src="%s" id="%s-js"></script>' . "\n",
		esc_url($src),
		esc_attr($handle)
	);
}, 10, 3);

add_action('wp_enqueue_scripts', function () {
	if (!function_exists('is_woocommerce')) {
		return;
	}

	if (is_cart() || is_checkout() || is_product()) {
		wp_dequeue_style('woocommerce-general');
		wp_dequeue_style('woocommerce-layout');
		wp_dequeue_style('woocommerce-smallscreen');
		wp_dequeue_style('wc-blocks-style');
		wp_dequeue_style('wc-blocks-vendors-style');
		wp_dequeue_style('wc-blocks-packages-style');
		wp_dequeue_style('woocommerce-blocktheme');
		wp_dequeue_script('wc-cart-block');
		wp_dequeue_script('wc-checkout-block');
		wp_dequeue_script('wc-blocks');
	}
}, 100);





// блоки сайта

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('italika_header_get_option')) {
	function italika_header_get_option($key, $default = '') {
		if (!function_exists('get_field')) {
			return $default;
		}

		$value = get_field($key, 'option');

		if ($value === null || $value === false || $value === '') {
			return $default;
		}

		return $value;
	}
}

if (!function_exists('italika_header_phone_href')) {
	function italika_header_phone_href($phone) {
		$phone = (string) $phone;
		$phone = preg_replace('/[^0-9\+]/', '', $phone);
		return $phone ? 'tel:' . $phone : '';
	}
}

if (!function_exists('italika_footer_get_option')) {
	function italika_footer_get_option($key, $default = '') {
		if (!function_exists('get_field')) {
			return $default;
		}

		$value = get_field($key, 'option');

		if ($value === null || $value === false) {
			return $default;
		}

		return $value;
	}
}

if (!function_exists('italika_get_acf_option_page_field')) {
	function italika_get_acf_option_page_field($key, $post_id, $default = '') {
		if (!function_exists('get_field')) {
			return $default;
		}

		$value = get_field($key, $post_id);

		if ($value === null || $value === false || $value === '') {
			return $default;
		}

		return $value;
	}
}

if (!function_exists('italika_about_page_get_option')) {
	function italika_about_page_get_option($key, $default = '') {
		return italika_get_acf_option_page_field($key, 'italika_about_page_settings', $default);
	}
}

if (!function_exists('italika_contacts_page_get_option')) {
	function italika_contacts_page_get_option($key, $default = '') {
		return italika_get_acf_option_page_field($key, 'italika_contacts_page_settings', $default);
	}
}

if (!function_exists('italika_delivery_page_get_option')) {
	function italika_delivery_page_get_option($key, $default = '') {
		return italika_get_acf_option_page_field($key, 'italika_delivery_page_settings', $default);
	}
}

if (!function_exists('italika_email_href')) {
	function italika_email_href($email) {
		$email = sanitize_email((string) $email);
		return $email ? 'mailto:' . $email : '';
	}
}

if (!function_exists('italika_get_home_categories_settings')) {
	function italika_get_home_categories_settings() {
		$settings = get_option('italika_home_categories_settings', []);

		if (!is_array($settings)) {
			$settings = [];
		}

		$settings['category_ids'] = isset($settings['category_ids']) && is_array($settings['category_ids'])
			? array_values(array_filter(array_map('intval', $settings['category_ids'])))
			: [];

		$settings['category_orders'] = isset($settings['category_orders']) && is_array($settings['category_orders'])
			? array_map('intval', $settings['category_orders'])
			: [];

		return $settings;
	}
}

if (!function_exists('italika_get_home_categories_selected_ids')) {
	function italika_get_home_categories_selected_ids() {
		$settings = italika_get_home_categories_settings();
		$category_ids = $settings['category_ids'];
		$category_orders = $settings['category_orders'];

		if (empty($category_ids)) {
			return [];
		}

		$items = [];

		foreach ($category_ids as $index => $term_id) {
			$order = isset($category_orders[$term_id]) ? (int) $category_orders[$term_id] : ($index + 1);

			if ($order < 1) {
				$order = $index + 1;
			}

			$items[] = [
				'term_id' => (int) $term_id,
				'order' => $order,
				'index' => $index,
			];
		}

		usort($items, static function ($a, $b) {
			if ($a['order'] === $b['order']) {
				return $a['index'] <=> $b['index'];
			}

			return $a['order'] <=> $b['order'];
		});

		return array_values(array_map(static function ($item) {
			return (int) $item['term_id'];
		}, $items));
	}
}

if (!function_exists('italika_wc_normalize_phone')) {
	function italika_wc_normalize_phone($phone) {
		return preg_replace('/[^0-9]/', '', (string) $phone);
	}
}

if (!function_exists('italika_wc_get_account_url')) {
	function italika_wc_get_account_url() {
		return function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/lkreg/');
	}
}

if (!function_exists('italika_wc_validate_registration_fields')) {
	function italika_wc_validate_registration_fields($errors, $username, $email) {
		$customer_details = function_exists('italika_wc_get_posted_customer_details') ? italika_wc_get_posted_customer_details() : [
			'customer_type' => 'individual',
			'company_name' => '',
			'company_inn' => '',
			'bonus_card_number' => '',
		];

		if (empty($_POST['billing_first_name'])) {
			$errors->add('billing_first_name_error', 'Укажите имя.');
		}

		if (empty($_POST['billing_phone'])) {
			$errors->add('billing_phone_error', 'Укажите телефон.');
		} else {
			$phone = sanitize_text_field(wp_unslash($_POST['billing_phone']));
			$normalized_phone = italika_wc_normalize_phone($phone);

			if (strlen($normalized_phone) < 7) {
				$errors->add('billing_phone_error', 'Укажите корректный телефон.');
			} else {
				$users = get_users([
					'fields' => 'ID',
					'number' => 1,
					'meta_query' => [
						[
							'key' => 'italika_normalized_phone',
							'value' => $normalized_phone,
						],
					],
				]);

				if (!empty($users)) {
					$errors->add('billing_phone_error', 'Пользователь с таким телефоном уже зарегистрирован.');
				} else {
					$users = get_users([
						'fields' => 'ID',
						'number' => 1,
						'meta_query' => [
							[
								'key' => 'billing_phone',
								'value' => $phone,
							],
						],
					]);

					if (!empty($users)) {
						$errors->add('billing_phone_error', 'Пользователь с таким телефоном уже зарегистрирован.');
					}
				}
			}
		}

		if (empty($_POST['agreement'])) {
			$errors->add('agreement_error', 'Подтвердите согласие на обработку персональных данных.');
		}

		if ($customer_details['customer_type'] === 'legal_entity' && $customer_details['company_name'] === '') {
			$errors->add('italika_company_name_error', 'Укажите наименование компании.');
		}

		if ($customer_details['customer_type'] === 'legal_entity' && $customer_details['company_inn'] === '') {
			$errors->add('italika_company_inn_error', 'Укажите ИНН.');
		}

		return $errors;
	}
	add_filter('woocommerce_registration_errors', 'italika_wc_validate_registration_fields', 10, 3);
}

if (!function_exists('italika_wc_save_registration_fields')) {
	function italika_wc_save_registration_fields($customer_id) {
		if (!$customer_id) {
			return;
		}

		$first_name = isset($_POST['billing_first_name']) ? sanitize_text_field(wp_unslash($_POST['billing_first_name'])) : '';
		$phone = isset($_POST['billing_phone']) ? sanitize_text_field(wp_unslash($_POST['billing_phone'])) : '';
		$customer_details = function_exists('italika_wc_get_posted_customer_details') ? italika_wc_get_posted_customer_details() : [
			'customer_type' => 'individual',
			'company_name' => '',
			'company_inn' => '',
			'bonus_card_number' => '',
		];

		if ($first_name !== '') {
			update_user_meta($customer_id, 'first_name', $first_name);
			update_user_meta($customer_id, 'billing_first_name', $first_name);
		}

		if ($phone !== '') {
			update_user_meta($customer_id, 'billing_phone', $phone);
			update_user_meta($customer_id, 'italika_normalized_phone', italika_wc_normalize_phone($phone));
		}

		update_user_meta($customer_id, 'italika_customer_type', $customer_details['customer_type']);
		update_user_meta($customer_id, 'italika_bonus_card_number', $customer_details['bonus_card_number']);

		if ($customer_details['customer_type'] === 'legal_entity') {
			update_user_meta($customer_id, 'italika_company_name', $customer_details['company_name']);
			update_user_meta($customer_id, 'italika_company_inn', $customer_details['company_inn']);
		} else {
			delete_user_meta($customer_id, 'italika_company_name');
			delete_user_meta($customer_id, 'italika_company_inn');
		}
	}
	add_action('woocommerce_created_customer', 'italika_wc_save_registration_fields');
}

if (!function_exists('italika_wc_sync_normalized_phone')) {
	function italika_wc_sync_normalized_phone($meta_id, $user_id, $meta_key, $meta_value) {
		if ($meta_key !== 'billing_phone') {
			return;
		}

		update_user_meta((int) $user_id, 'italika_normalized_phone', italika_wc_normalize_phone($meta_value));
	}
	add_action('updated_user_meta', 'italika_wc_sync_normalized_phone', 10, 4);
	add_action('added_user_meta', 'italika_wc_sync_normalized_phone', 10, 4);
}

if (!function_exists('italika_wc_authenticate_by_phone')) {
	function italika_wc_authenticate_by_phone($user, $username, $password) {
		if ($user instanceof WP_User || $username === '' || $password === '') {
			return $user;
		}

		$phone = italika_wc_normalize_phone($username);

		if (strlen($phone) < 7) {
			return $user;
		}

		$users = get_users([
			'fields' => ['ID', 'user_login'],
			'number' => 1,
			'meta_query' => [
				[
					'key' => 'italika_normalized_phone',
					'value' => $phone,
				],
			],
		]);

		if (empty($users)) {
			$users = get_users([
				'fields' => ['ID', 'user_login'],
				'number' => 1,
				'meta_query' => [
					[
						'key' => 'billing_phone',
						'value' => $username,
					],
				],
			]);
		}

		if (empty($users[0]->user_login)) {
			return $user;
		}

		return wp_authenticate_username_password(null, $users[0]->user_login, $password);
	}
	add_filter('authenticate', 'italika_wc_authenticate_by_phone', 20, 3);
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
	add_filter('woocommerce_add_to_cart_fragments', 'italika_wc_cart_count_fragment');
}

if (!function_exists('italika_wc_redirect_unused_account_endpoints')) {
	function italika_wc_redirect_unused_account_endpoints() {
		if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('edit-address')) {
			wp_safe_redirect(italika_wc_get_account_url());
			exit;
		}
	}
	add_action('template_redirect', 'italika_wc_redirect_unused_account_endpoints');
}

if (!function_exists('italika_wc_account_menu_items')) {
	function italika_wc_account_menu_items($items) {
		unset($items['edit-address']);

		return $items;
	}
	add_filter('woocommerce_account_menu_items', 'italika_wc_account_menu_items', 20);
}

if (!function_exists('italika_wc_ensure_catalog_shop_slug')) {
	function italika_wc_ensure_catalog_shop_slug() {
		if (!function_exists('wc_get_page_id')) {
			return;
		}

		$shop_id = (int) wc_get_page_id('shop');

		if ($shop_id <= 0) {
			return;
		}

		$shop_page = get_post($shop_id);

		if (!$shop_page || $shop_page->post_name === 'catalog') {
			return;
		}

		$existing_catalog_page = get_page_by_path('catalog');

		if ($existing_catalog_page && (int) $existing_catalog_page->ID !== $shop_id) {
			return;
		}

		wp_update_post([
			'ID' => $shop_id,
			'post_name' => 'catalog',
		]);

		if (get_option('italika_catalog_shop_slug_flushed') !== 'yes') {
			flush_rewrite_rules(false);
			update_option('italika_catalog_shop_slug_flushed', 'yes', false);
		}
	}
	add_action('init', 'italika_wc_ensure_catalog_shop_slug', 30);
}

if (!function_exists('italika_register_theme_blocks_menu')) {
	function italika_register_theme_blocks_menu() {
		add_menu_page(
			'Блоки сайта',
			'Блоки сайта',
			'edit_posts',
			'italika-theme-blocks-settings',
			'italika_render_theme_blocks_redirect_page',
			'dashicons-layout',
			61
		);

		add_submenu_page(
			'italika-theme-blocks-settings',
			'Титульные категории на главной',
			'Категории',
			'edit_posts',
			'italika-home-categories-settings',
			'italika_render_home_categories_admin_page'
		);
	}
	add_action('admin_menu', 'italika_register_theme_blocks_menu', 20);
}

if (!function_exists('italika_cleanup_theme_blocks_menu')) {
	function italika_cleanup_theme_blocks_menu() {
		remove_submenu_page('italika-theme-blocks-settings', 'italika-theme-blocks-settings');
	}
	add_action('admin_menu', 'italika_cleanup_theme_blocks_menu', 999);
}

if (!function_exists('italika_register_theme_blocks_acf_pages')) {
	function italika_register_theme_blocks_acf_pages() {
		if (!function_exists('acf_add_options_sub_page')) {
			return;
		}

		acf_add_options_sub_page([
			'page_title'  => 'Шапка',
			'menu_title'  => 'Шапка',
			'menu_slug'   => 'italika-header-settings',
			'parent_slug' => 'italika-theme-blocks-settings',
			'capability'  => 'edit_posts',
		]);

		acf_add_options_sub_page([
			'page_title'  => 'Слайдер на главной',
			'menu_title'  => 'Слайдер',
			'menu_slug'   => 'italika-home-hero-settings',
			'parent_slug' => 'italika-theme-blocks-settings',
			'capability'  => 'edit_posts',
		]);

		acf_add_options_sub_page([
			'page_title'  => 'О компании',
			'menu_title'  => 'О компании',
			'menu_slug'   => 'italika-about-promo-settings',
			'parent_slug' => 'italika-theme-blocks-settings',
			'capability'  => 'edit_posts',
		]);

		acf_add_options_sub_page([
			'page_title'  => 'Страница «Контакты»',
			'menu_title'  => 'Страница «Контакты»',
			'menu_slug'   => 'italika-contacts-page-settings',
			'post_id'     => 'italika_contacts_page_settings',
			'parent_slug' => 'italika-theme-blocks-settings',
			'capability'  => 'edit_posts',
		]);

		acf_add_options_sub_page([
			'page_title'  => 'Страница «О компании»',
			'menu_title'  => 'Страница «О компании»',
			'menu_slug'   => 'italika-about-page-settings',
			'post_id'     => 'italika_about_page_settings',
			'parent_slug' => 'italika-theme-blocks-settings',
			'capability'  => 'edit_posts',
		]);

		acf_add_options_sub_page([
			'page_title'  => 'Страница «Доставка и оплата»',
			'menu_title'  => 'Страница «Доставка и оплата»',
			'menu_slug'   => 'italika-delivery-page-settings',
			'post_id'     => 'italika_delivery_page_settings',
			'parent_slug' => 'italika-theme-blocks-settings',
			'capability'  => 'edit_posts',
		]);

		acf_add_options_sub_page([
			'page_title'  => 'Подвал',
			'menu_title'  => 'Подвал',
			'menu_slug'   => 'italika-footer-settings',
			'parent_slug' => 'italika-theme-blocks-settings',
			'capability'  => 'edit_posts',
		]);
	}
	add_action('acf/init', 'italika_register_theme_blocks_acf_pages');
}

if (!function_exists('italika_render_theme_blocks_redirect_page')) {
	function italika_render_theme_blocks_redirect_page() {
		wp_safe_redirect(admin_url('admin.php?page=italika-header-settings'));
		exit;
	}
}

if (!function_exists('italika_save_home_categories_settings')) {
	function italika_save_home_categories_settings() {
		if (!is_admin()) {
			return;
		}

		if (!isset($_POST['italika_home_categories_settings_submit'])) {
			return;
		}

		if (!current_user_can('edit_posts')) {
			return;
		}

		check_admin_referer('italika_save_home_categories_settings', 'italika_home_categories_nonce');

		$category_ids = isset($_POST['home_catalog_promo_category_ids']) && is_array($_POST['home_catalog_promo_category_ids'])
			? array_values(array_filter(array_map('intval', wp_unslash($_POST['home_catalog_promo_category_ids']))))
			: [];

		$posted_orders = isset($_POST['home_catalog_promo_category_orders']) && is_array($_POST['home_catalog_promo_category_orders'])
			? wp_unslash($_POST['home_catalog_promo_category_orders'])
			: [];

		$category_orders = [];
		$fallback_order = 1;

		foreach ($category_ids as $term_id) {
			$order = isset($posted_orders[$term_id]) ? (int) $posted_orders[$term_id] : $fallback_order;

			if ($order < 1) {
				$order = $fallback_order;
			}

			$category_orders[$term_id] = $order;
			$fallback_order++;
		}

		update_option('italika_home_categories_settings', [
			'category_ids' => $category_ids,
			'category_orders' => $category_orders,
		]);

		wp_safe_redirect(add_query_arg([
			'page' => 'italika-home-categories-settings',
			'updated' => 'true',
		], admin_url('admin.php')));
		exit;
	}
	add_action('admin_init', 'italika_save_home_categories_settings');
}

if (!function_exists('italika_render_home_categories_admin_page')) {
	function italika_render_home_categories_admin_page() {
		$settings = italika_get_home_categories_settings();
		$selected_ids = $settings['category_ids'];
		$selected_map = array_fill_keys($selected_ids, true);
		$category_orders = $settings['category_orders'];

		$terms = get_terms([
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		]);

		$terms = !is_wp_error($terms) && is_array($terms) ? $terms : [];
		$default_order = 1;
		?>
		<div class="wrap">
			<h1>Титульные категории на главной</h1>

			<?php if (isset($_GET['updated']) && $_GET['updated'] === 'true') : ?>
				<div class="notice notice-success is-dismissible">
					<p>Настройки сохранены.</p>
				</div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field('italika_save_home_categories_settings', 'italika_home_categories_nonce'); ?>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">Категории WooCommerce</th>
							<td>
								<div style="border:1px solid #dcdcde;border-radius:8px;padding:16px;max-width:1100px;max-height:720px;overflow:auto;background:#fff;">
									<?php if (!empty($terms)) : ?>
										<div style="display:grid;grid-template-columns:minmax(0,1fr) 120px;gap:12px 16px;align-items:center;">
											<div style="font-weight:600;">Категория</div>
											<div style="font-weight:600;">Порядок</div>

											<?php foreach ($terms as $term) : ?>
												<?php
												$term_id = (int) $term->term_id;
												$is_checked = isset($selected_map[$term_id]);
												$current_order = isset($category_orders[$term_id]) ? (int) $category_orders[$term_id] : $default_order;

												if ($current_order < 1) {
													$current_order = $default_order;
												}

												$default_order++;
												?>
												<label style="display:flex;align-items:center;gap:10px;margin:0;line-height:1.35;">
													<input type="checkbox" name="home_catalog_promo_category_ids[]" value="<?php echo esc_attr((string) $term_id); ?>" <?php checked($is_checked); ?> style="margin:0;flex:0 0 auto;">
													<span><?php echo esc_html($term->name); ?></span>
												</label>
												<input type="number" name="home_catalog_promo_category_orders[<?php echo esc_attr((string) $term_id); ?>]" value="<?php echo esc_attr((string) $current_order); ?>" min="1" step="1" style="width:100px;margin:0;">
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<p style="margin:0;">Категории не найдены.</p>
									<?php endif; ?>
								</div>
								<p class="description">Отметь нужные категории и задай номер порядка. Если ничего не отмечено — блок не выводится.</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" name="italika_home_categories_settings_submit" class="button button-primary">Сохранить</button>
				</p>
			</form>
		</div>
		<?php
	}
}

if (!function_exists('italika_register_header_menu')) {
	function italika_register_header_menu() {
		register_nav_menus([
			'header_menu' => 'Header Menu',
		]);
	}
	add_action('after_setup_theme', 'italika_register_header_menu');
}

if (!class_exists('Italika_Header_Menu_Walker')) {
	class Italika_Header_Menu_Walker extends Walker_Nav_Menu {
		public function start_lvl(&$output, $depth = 0, $args = null) {
		}

		public function end_lvl(&$output, $depth = 0, $args = null) {
		}

		public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
			if ((int) $depth !== 0) {
				return;
			}

			$classes = is_array($item->classes) ? array_filter($item->classes) : [];

			if (in_array('menu-divider', $classes, true)) {
				$output .= '<span class="nav__divider" aria-hidden="true"></span>';
				return;
			}

			$link_classes = ['nav__item'];

			if (in_array('current-menu-item', $classes, true) || in_array('current_page_item', $classes, true) || in_array('current-menu-ancestor', $classes, true)) {
				$link_classes[] = 'is-active';
			}

			if (in_array('menu-feature', $classes, true)) {
				$link_classes[] = 'nav__item--feature';
			}

			if (in_array('menu-catalog', $classes, true)) {
				$link_classes[] = 'nav__item--catalog';
			}

			if (in_array('menu-sale', $classes, true)) {
				$link_classes[] = 'nav__item--sale';
			}

			$atts = [];
			$atts['class'] = implode(' ', array_unique($link_classes));
			$atts['href'] = !empty($item->url) ? $item->url : '#';

			if (!empty($item->target)) {
				$atts['target'] = $item->target;
			}

			if (!empty($item->xfn)) {
				$atts['rel'] = $item->xfn;
			}

			$attributes = '';
			foreach ($atts as $attr => $value) {
				$attributes .= ' ' . $attr . '="' . esc_attr($value) . '"';
			}

			$icon = '';

			if (in_array('menu-catalog', $classes, true)) {
				$icon = '<span class="nav__item-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="4" height="4" rx="1"></rect><rect x="10" y="3" width="4" height="4" rx="1"></rect><rect x="17" y="3" width="4" height="4" rx="1"></rect><rect x="3" y="10" width="4" height="4" rx="1"></rect><rect x="10" y="10" width="4" height="4" rx="1"></rect><rect x="17" y="10" width="4" height="4" rx="1"></rect><rect x="3" y="17" width="4" height="4" rx="1"></rect><rect x="10" y="17" width="4" height="4" rx="1"></rect><rect x="17" y="17" width="4" height="4" rx="1"></rect></svg></span>';
			} elseif (in_array('menu-sale', $classes, true)) {
				$icon = '<span class="nav__item-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M13.5 2 5 13h5l-1.5 9L19 10h-5L13.5 2Z"></path></svg></span>';
			}

			$title = apply_filters('the_title', $item->title, $item->ID);

			$output .= '<a' . $attributes . '>' . $icon . '<span>' . esc_html($title) . '</span></a>';
		}

		public function end_el(&$output, $item, $depth = 0, $args = null) {
		}
	}
}





require_once get_template_directory() . '/modules/favorites/favorites.php';

require_once get_template_directory() . '/modules/news-strip/news-strip.php';

require_once get_template_directory() . '/modules/recipes-strip/recipes-strip.php';

require_once get_template_directory() . '/modules/about-promo/about-promo.php';

require_once get_template_directory() . '/inc/product-sort.php';

require_once get_template_directory() . '/inc/sale-products-section.php';

require_once get_template_directory() . '/inc/ecomcard.php';

require_once get_template_directory() . '/inc/product-page.php';

require_once get_template_directory() . '/inc/catalog-page.php';

require_once get_template_directory() . '/inc/search-page.php';

require_once get_template_directory() . '/inc/newsletter.php';

require_once get_template_directory() . '/inc/recipes-post-type.php';

require_once get_template_directory() . '/inc/posts-archive.php';

require_once get_template_directory() . '/inc/spam-protection.php';

require_once get_template_directory() . '/inc/woocommerce.php';

require_once get_template_directory() . '/inc/order-export.php';

require_once get_template_directory() . '/inc/one-c-stock-import.php';

require_once get_template_directory() . '/inc/old-website-import-cleanup.php';

require_once get_template_directory() . '/inc/site-version-choice.php';

require_once get_template_directory() . '/inc/cookie-consent.php';

require_once get_template_directory() . '/inc/telegram-bot/telegram-bot.php';

require_once get_template_directory() . '/inc/max-bot/max-bot.php';

require_once get_template_directory() . '/inc/seo/seo.php';

add_filter('italika_favorites_login_trigger_selector', function () {
    return '.js-header-account-trigger';
});


add_filter('post_link', function ($permalink, $post) {
	if ($post->post_type !== 'post') {
		return $permalink;
	}

	return home_url('/news/' . $post->post_name . '/');
}, 10, 2);

add_action('init', function () {
	add_rewrite_rule('^news/([^/]+)/?$', 'index.php?name=$matches[1]', 'top');
});
