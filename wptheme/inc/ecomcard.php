<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_ecomcard_format_money')) {
    function italika_ecomcard_format_money($amount)
    {
        return number_format((float) $amount, 2, ',', ' ') . ' ₽';
    }
}

if (!function_exists('italika_ecomcard_is_available')) {
    function italika_ecomcard_is_available($product)
    {
        if (!$product || !is_a($product, 'WC_Product')) {
            return false;
        }

        if (!$product->is_in_stock()) {
            return false;
        }

        if ($product->managing_stock()) {
            $qty = $product->get_stock_quantity();

            if ($qty !== null) {
                return (float) $qty > 0;
            }
        }

        return true;
    }
}

if (!function_exists('italika_ecomcard_is_on_request')) {
    function italika_ecomcard_is_on_request($product)
    {
        if (!$product || !is_a($product, 'WC_Product')) {
            return false;
        }

        return (float) wc_get_price_to_display($product) <= 0;
    }
}

if (!function_exists('italika_ecomcard_get_data')) {
    function italika_ecomcard_get_data($product_id, $args = [])
    {
        if (!function_exists('wc_get_product')) {
            return [];
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            return [];
        }

        $defaults = [
            'image_size' => 'woocommerce_thumbnail',
            'show_benefit' => true,
            'show_old_price' => true,
            'show_favorite' => true,
            'show_stock' => true,
            'show_cart' => true,
            'card_class' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $image_id = $product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, $args['image_size']) : '';
        $image_url = $image_url ? $image_url : wc_placeholder_img_src();

        $title = $product->get_name();
        $permalink = get_permalink($product->get_id());

        $regular_price = (float) $product->get_regular_price();
        $sale_price_raw = $product->get_sale_price();
        $sale_price = $sale_price_raw !== '' ? (float) $sale_price_raw : 0.0;
        $current_price = (float) wc_get_price_to_display($product);

        $is_sale = $product->is_on_sale() && $regular_price > 0 && $sale_price_raw !== '' && $sale_price > 0 && $sale_price < $regular_price;
        $old_price = $is_sale ? $regular_price : 0.0;

        $discount_percent = 0;
        $savings = 0.0;

        if ($is_sale) {
            $discount_percent = (int) round((($regular_price - $sale_price) / $regular_price) * 100);
            $savings = max(0, $regular_price - $sale_price);
        }

        $is_on_request = italika_ecomcard_is_on_request($product);
        $is_available = $is_on_request || italika_ecomcard_is_available($product);
        $stock_text = $is_on_request ? 'Под заказ' : ($is_available ? 'В наличии' : 'В пути');
        $stock_modifier = $is_available ? ' sale-products__stock--available' : ' sale-products__stock--waiting';

        $user_id = get_current_user_id();
        $is_logged_in = $user_id > 0;
        $is_favorite = $is_logged_in && function_exists('italika_favorites_is_favorite')
            ? italika_favorites_is_favorite($product->get_id(), $user_id)
            : false;

        $favorite_label = $is_favorite
            ? 'Убрать из избранного: ' . $title
            : 'Добавить в избранное: ' . $title;

        $favorite_active_class = $is_favorite ? ' is-active' : '';
        $favorite_pressed = $is_favorite ? 'true' : 'false';
        $auth_required = $is_logged_in ? '0' : '1';

        $cart_url = '';
        $cart_text = 'В пути';
        $cart_classes = 'sale-products__cart js-ecomcard-cart';
        $cart_disabled = true;
        $cart_aria = 'В пути: ' . $title;
        $product_type = $product->get_type();

        if ($is_available && $product->is_purchasable()) {
            $cart_url = $product->add_to_cart_url();
            $cart_text = $product->add_to_cart_text();
            $cart_disabled = false;
            $cart_aria = $cart_text . ': ' . $title;
            $cart_classes .= ' product_type_' . sanitize_html_class($product_type);

            if ($product->supports('ajax_add_to_cart')) {
                $cart_classes .= ' add_to_cart_button ajax_add_to_cart';
            }
        }

        return [
            'product_id' => $product->get_id(),
            'title' => $title,
            'permalink' => $permalink,
            'image_url' => $image_url,
            'is_sale' => $is_sale,
            'is_on_request' => $is_on_request,
            'current_price' => $current_price,
            'old_price' => $old_price,
            'discount_percent' => $discount_percent,
            'savings' => $savings,
            'is_available' => $is_available,
            'stock_text' => $stock_text,
            'stock_modifier' => $stock_modifier,
            'is_logged_in' => $is_logged_in,
            'is_favorite' => $is_favorite,
            'favorite_label' => $favorite_label,
            'favorite_active_class' => $favorite_active_class,
            'favorite_pressed' => $favorite_pressed,
            'auth_required' => $auth_required,
            'cart_url' => $cart_url,
            'cart_text' => $cart_text,
            'cart_classes' => $cart_classes,
            'cart_disabled' => $cart_disabled,
            'cart_aria' => $cart_aria,
            'product_type' => $product_type,
            'sku' => $product->get_sku(),
            'card_class' => trim((string) $args['card_class']),
            'show_benefit' => !empty($args['show_benefit']),
            'show_old_price' => !empty($args['show_old_price']),
            'show_favorite' => !empty($args['show_favorite']),
            'show_stock' => !empty($args['show_stock']),
            'show_cart' => !empty($args['show_cart']),
        ];
    }
}

if (!function_exists('italika_ecomcard_render')) {
    function italika_ecomcard_render($product_id, $args = [], $echo = false)
    {
        $data = italika_ecomcard_get_data($product_id, $args);

        if (!$data) {
            return '';
        }

        $card_classes = 'sale-products__card ecomcard';
        if ($data['card_class'] !== '') {
            $card_classes .= ' ' . $data['card_class'];
        }

        ob_start();
        ?>
<article class="<?php echo esc_attr($card_classes); ?>" data-product-id="<?php echo esc_attr($data['product_id']); ?>">
    <?php if ($data['is_sale']) : ?>
        <span class="sale-products__badges">
            <span class="sale-products__badge">Акция</span>
        </span>
    <?php endif; ?>

    <?php if ($data['show_favorite']) : ?>
        <button
            class="sale-products__favorite js-italika-favorite<?php echo esc_attr($data['favorite_active_class']); ?>"
            type="button"
            aria-label="<?php echo esc_attr($data['favorite_label']); ?>"
            aria-pressed="<?php echo esc_attr($data['favorite_pressed']); ?>"
            data-product-id="<?php echo esc_attr($data['product_id']); ?>"
            data-auth-required="<?php echo esc_attr($data['auth_required']); ?>">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 20.2 4.7 13.4A4.9 4.9 0 0 1 11.5 6l.5.6.5-.6a4.9 4.9 0 0 1 6.8 7.3L12 20.2Z" stroke-width="1.8" stroke-linejoin="round"></path>
            </svg>
        </button>
    <?php endif; ?>

    <a class="sale-products__image-box" href="<?php echo esc_url($data['permalink']); ?>">
        <img
            class="sale-products__image"
            src="<?php echo esc_url($data['image_url']); ?>"
            alt="<?php echo esc_attr($data['title']); ?>"
            loading="lazy"
            decoding="async">
    </a>

    <span class="sale-products__content">
        <a class="sale-products__title-text" href="<?php echo esc_url($data['permalink']); ?>">
            <?php echo esc_html($data['title']); ?>
        </a>

        <?php if ($data['show_stock']) : ?>
            <span class="sale-products__stock<?php echo esc_attr($data['stock_modifier']); ?>">
                <?php echo esc_html($data['stock_text']); ?>
            </span>
        <?php endif; ?>

        <span class="sale-products__price-block">
            <span class="sale-products__price-current">
                <?php echo esc_html($data['is_on_request'] ? 'Под заказ' : italika_ecomcard_format_money($data['current_price'])); ?>
            </span>

            <?php if ($data['is_sale'] && $data['show_old_price']) : ?>
                <span class="sale-products__price-old">
                    <?php echo esc_html(italika_ecomcard_format_money($data['old_price'])); ?>
                </span>
            <?php endif; ?>

            <?php if ($data['is_sale'] && $data['show_benefit']) : ?>
                <span class="sale-products__benefit">
                    <span class="sale-products__discount">-<?php echo esc_html($data['discount_percent']); ?>%</span>
                    <span class="sale-products__benefit-text">Экономия <?php echo esc_html(italika_ecomcard_format_money($data['savings'])); ?></span>
                </span>
            <?php endif; ?>
        </span>

        <?php if ($data['show_cart']) : ?>
            <?php if (!$data['cart_disabled'] && $data['cart_url'] !== '') : ?>
                <a
                    href="<?php echo esc_url($data['cart_url']); ?>"
                    data-quantity="1"
                    data-product_id="<?php echo esc_attr($data['product_id']); ?>"
                    data-product_sku="<?php echo esc_attr($data['sku']); ?>"
                    aria-label="<?php echo esc_attr($data['cart_aria']); ?>"
                    rel="nofollow"
                    class="<?php echo esc_attr($data['cart_classes']); ?>">
                    <span class="sale-products__cart-text"><?php echo esc_html($data['cart_text']); ?></span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3.5 5H6l1.6 8.1c.1.7.7 1.2 1.4 1.2h7.7c.7 0 1.3-.5 1.4-1.2L19.5 8H7.1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                        <circle cx="10" cy="18.2" r="1.2" fill="currentColor" stroke="none"></circle>
                        <circle cx="17" cy="18.2" r="1.2" fill="currentColor" stroke="none"></circle>
                    </svg>
                </a>
            <?php else : ?>
                <button
                    class="sale-products__cart js-ecomcard-cart"
                    type="button"
                    aria-label="<?php echo esc_attr($data['cart_aria']); ?>"
                    disabled>
                    <span class="sale-products__cart-text"><?php echo esc_html($data['cart_text']); ?></span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3.5 5H6l1.6 8.1c.1.7.7 1.2 1.4 1.2h7.7c.7 0 1.3-.5 1.4-1.2L19.5 8H7.1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                        <circle cx="10" cy="18.2" r="1.2" fill="currentColor" stroke="none"></circle>
                        <circle cx="17" cy="18.2" r="1.2" fill="currentColor" stroke="none"></circle>
                    </svg>
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </span>
</article>
        <?php
        $html = ob_get_clean();

        if ($echo) {
            echo $html;
        }

        return $html;
    }
}
