<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_product_page_format_money')) {
	function italika_product_page_format_money($amount) {
		if (function_exists('italika_ecomcard_format_money')) {
			return italika_ecomcard_format_money($amount);
		}

		return number_format((float) $amount, 2, ',', ' ') . ' ₽';
	}
}

if (!function_exists('italika_product_page_get_gallery')) {
	function italika_product_page_get_gallery($product) {
		if (!$product || !is_a($product, 'WC_Product')) {
			return [];
		}

		$image_ids = [];
		$main_image_id = (int) $product->get_image_id();

		if ($main_image_id > 0) {
			$image_ids[] = $main_image_id;
		}

		foreach ((array) $product->get_gallery_image_ids() as $image_id) {
			$image_id = (int) $image_id;

			if ($image_id > 0 && !in_array($image_id, $image_ids, true)) {
				$image_ids[] = $image_id;
			}
		}

		$images = [];

		foreach ($image_ids as $image_id) {
			$full = wp_get_attachment_image_url($image_id, 'woocommerce_single');
			$thumb = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');

			if (!$full) {
				continue;
			}

			$images[] = [
				'full' => $full,
				'thumb' => $thumb ? $thumb : $full,
				'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
			];
		}

		if (empty($images)) {
			$placeholder = wc_placeholder_img_src('woocommerce_single');
			$images[] = [
				'full' => $placeholder,
				'thumb' => $placeholder,
				'alt' => $product->get_name(),
			];
		}

		return $images;
	}
}

if (!function_exists('italika_product_page_get_specs')) {
	function italika_product_page_get_specs($product) {
		if (!$product || !is_a($product, 'WC_Product')) {
			return [];
		}

		$specs = [];
		$weight = $product->get_weight();
		$dimensions = wc_format_dimensions($product->get_dimensions(false));
		$categories = wc_get_product_category_list($product->get_id(), ', ');

		if ($weight !== '') {
			$specs[] = [
				'label' => 'Вес',
				'value' => wc_format_weight($weight),
			];
		}

		if ($categories !== '') {
			$specs[] = [
				'label' => 'Категория',
				'value' => wp_strip_all_tags($categories),
			];
		}

		if ($dimensions && $dimensions !== 'Н/Д') {
			$specs[] = [
				'label' => 'Размер',
				'value' => wp_strip_all_tags($dimensions),
			];
		}

		foreach ($product->get_attributes() as $attribute) {
			if (!$attribute || !$attribute->get_visible()) {
				continue;
			}

			$label = wc_attribute_label($attribute->get_name());
			$value = '';

			if ($attribute->is_taxonomy()) {
				$terms = wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
				$value = implode(', ', $terms);
			} else {
				$value = implode(', ', $attribute->get_options());
			}

			if ($label !== '' && $value !== '') {
				$specs[] = [
					'label' => $label,
					'value' => $value,
				];
			}
		}

		return $specs;
	}
}

if (!function_exists('italika_product_page_get_price_data')) {
	function italika_product_page_get_price_data($product) {
		$current_price = (float) wc_get_price_to_display($product);
		$regular_price = (float) $product->get_regular_price();
		$sale_price_raw = $product->get_sale_price();
		$sale_price = $sale_price_raw !== '' ? (float) $sale_price_raw : 0.0;
		$is_sale = $product->is_on_sale() && $regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price;

		return [
			'current' => $current_price,
			'old' => $is_sale ? $regular_price : 0.0,
			'savings' => $is_sale ? max(0, $regular_price - $sale_price) : 0.0,
			'is_sale' => $is_sale,
			'html' => wp_strip_all_tags($product->get_price_html()),
		];
	}
}

if (!function_exists('italika_product_page_render')) {
	function italika_product_page_render($product_id = 0, $echo = true) {
		$product_id = $product_id ? (int) $product_id : get_the_ID();
		$product = function_exists('wc_get_product') ? wc_get_product($product_id) : null;

		if (!$product) {
			return '';
		}

		$gallery = italika_product_page_get_gallery($product);
		$main_image = $gallery[0];
		$title = $product->get_name();
		$sku = $product->get_sku();
		$price = italika_product_page_get_price_data($product);
		$specs = italika_product_page_get_specs($product);
		$is_on_request = function_exists('italika_ecomcard_is_on_request') ? italika_ecomcard_is_on_request($product) : ((float) wc_get_price_to_display($product) <= 0);
		$is_available = $is_on_request || (function_exists('italika_ecomcard_is_available') ? italika_ecomcard_is_available($product) : $product->is_in_stock());
		$stock_text = $is_on_request ? 'Под заказ' : ($is_available ? 'В наличии' : 'В пути');
		$stock_class = $is_available ? ' product-page__stock--available' : '';
		$description = $product->get_description();
		$short_description = $product->get_short_description();
		$description = $description !== '' ? $description : $short_description;
		$description = $description !== '' ? $description : 'Описание товара скоро появится.';
		$user_id = get_current_user_id();
		$is_logged_in = $user_id > 0;
		$is_favorite = $is_logged_in && function_exists('italika_favorites_is_favorite')
			? italika_favorites_is_favorite($product->get_id(), $user_id)
			: false;
		$favorite_label = $is_favorite ? 'Убрать из избранного: ' . $title : 'Добавить в избранное: ' . $title;
		$favorite_class = $is_favorite ? ' is-active' : '';
		$favorite_pressed = $is_favorite ? 'true' : 'false';
		$auth_required = $is_logged_in ? '0' : '1';
		$cart_text = $is_available && $product->is_purchasable() ? $product->add_to_cart_text() : 'В пути';
		$cart_classes = 'product-page__cart js-product-cart';
		$cart_url = $product->add_to_cart_url();
		$cart_disabled = !$is_available || !$product->is_purchasable();
		$max_purchase_quantity = (int) $product->get_max_purchase_quantity();
		$max_purchase_quantity = $max_purchase_quantity > 0 ? $max_purchase_quantity : 99;

		if ($product->supports('ajax_add_to_cart')) {
			$cart_classes .= ' add_to_cart_button ajax_add_to_cart';
		}

		$cart_classes .= ' product_type_' . sanitize_html_class($product->get_type());

		ob_start();
		?>
<section class="product-page" data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>">
	<div class="container">
		<?php wc_print_notices(); ?>

		<div class="product-page__layout">
			<div class="product-page__gallery" aria-label="Галерея товара">
				<div class="product-page__preview">
					<?php if ($price['is_sale']) : ?>
						<span class="product-page__badge">Акция</span>
					<?php endif; ?>

					<img
						class="product-page__image js-product-image"
						src="<?php echo esc_url($main_image['full']); ?>"
						alt="<?php echo esc_attr($main_image['alt'] ? $main_image['alt'] : $title); ?>"
						loading="eager"
						decoding="async">
				</div>

				<?php if (count($gallery) > 1) : ?>
					<div class="product-page__thumbs">
						<?php foreach ($gallery as $index => $image) : ?>
							<button
								class="product-page__thumb js-product-thumb<?php echo $index === 0 ? ' is-active' : ''; ?>"
								type="button"
								data-image="<?php echo esc_url($image['full']); ?>"
								aria-label="<?php echo esc_attr('Фото товара ' . ($index + 1)); ?>">
								<img
									src="<?php echo esc_url($image['thumb']); ?>"
									alt=""
									loading="lazy"
									decoding="async">
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="product-page__content">
				<p class="product-page__eyebrow"><?php echo esc_html($sku ? 'Артикул: ' . $sku : 'Товар Italika'); ?></p>
				<h1 class="product-page__title"><?php echo esc_html($title); ?></h1>

				<div class="product-page__meta">
					<span class="product-page__stock<?php echo esc_attr($stock_class); ?>"><?php echo esc_html($stock_text); ?></span>
					<span>Поставка: 1-2 дня</span>
				</div>

				<div class="product-page__buy">
					<div class="product-page__price">
						<?php if ($price['current'] > 0) : ?>
							<strong><?php echo esc_html(italika_product_page_format_money($price['current'])); ?>/шт</strong>
						<?php elseif ($is_on_request) : ?>
							<strong>Под заказ</strong>
						<?php elseif ($price['html'] !== '') : ?>
							<strong><?php echo esc_html($price['html']); ?></strong>
						<?php else : ?>
							<strong>Цена по запросу</strong>
						<?php endif; ?>

						<?php if ($price['is_sale']) : ?>
							<span><?php echo esc_html(italika_product_page_format_money($price['old'])); ?></span>
						<?php endif; ?>
					</div>

					<?php if ($price['is_sale']) : ?>
						<div class="product-page__discount">Экономия <?php echo esc_html(italika_product_page_format_money($price['savings'])); ?></div>
					<?php endif; ?>

					<div class="product-page__actions" aria-label="Покупка товара">
						<div class="product-page__quantity" aria-label="Количество">
							<button class="product-page__quantity-button js-product-minus" type="button" aria-label="Уменьшить количество">-</button>
							<input
								class="product-page__quantity-input js-product-quantity"
								type="number"
								min="1"
								max="<?php echo esc_attr((string) $max_purchase_quantity); ?>"
								value="1"
								inputmode="numeric">
							<button class="product-page__quantity-button js-product-plus" type="button" aria-label="Увеличить количество">+</button>
						</div>

						<?php if (!$cart_disabled && $cart_url !== '') : ?>
							<a
								class="<?php echo esc_attr($cart_classes); ?>"
								href="<?php echo esc_url($cart_url); ?>"
								data-quantity="1"
								data-product_id="<?php echo esc_attr((string) $product->get_id()); ?>"
								data-product_sku="<?php echo esc_attr($sku); ?>"
								aria-label="<?php echo esc_attr($cart_text . ': ' . $title); ?>"
								rel="nofollow"><?php echo esc_html($cart_text); ?></a>
						<?php else : ?>
							<button class="product-page__cart js-product-cart" type="button" disabled><?php echo esc_html($cart_text); ?></button>
						<?php endif; ?>

						<button
							class="product-page__favorite js-italika-favorite<?php echo esc_attr($favorite_class); ?>"
							type="button"
							aria-label="<?php echo esc_attr($favorite_label); ?>"
							aria-pressed="<?php echo esc_attr($favorite_pressed); ?>"
							data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>"
							data-auth-required="<?php echo esc_attr($auth_required); ?>">
							<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M12 20.2 4.7 13.4A4.9 4.9 0 0 1 11.5 6l.5.6.5-.6a4.9 4.9 0 0 1 6.8 7.3L12 20.2Z" stroke-width="1.8" stroke-linejoin="round"></path>
							</svg>
						</button>
					</div>
				</div>

				<div class="product-page__info">
					<div class="product-page__info-block">
						<h2>Описание</h2>
						<?php echo wp_kses_post(wpautop($description)); ?>
					</div>

					<?php if (!empty($specs)) : ?>
						<div class="product-page__info-block">
							<h2>Характеристики</h2>
							<dl class="product-page__specs">
								<?php foreach ($specs as $spec) : ?>
									<div>
										<dt><?php echo esc_html($spec['label']); ?></dt>
										<dd><?php echo esc_html($spec['value']); ?></dd>
									</div>
								<?php endforeach; ?>
							</dl>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>
		<?php
		$html = ob_get_clean();

		if ($echo) {
			echo $html;
		}

		return $html;
	}
}
