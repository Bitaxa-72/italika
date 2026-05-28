<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_sale_products_get_ids')) {
	function italika_sale_products_get_ids($limit = 0, $offset = 0)
	{
		if (!function_exists('wc_get_product_ids_on_sale')) {
			return [];
		}

		$ids = wc_get_product_ids_on_sale();

		if (!$ids) {
			return [];
		}

		$args = [
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => $limit > 0 ? (int) $limit : -1,
			'offset' => max(0, (int) $offset),
			'post__in' => array_map('intval', $ids),
			'fields' => 'ids',
			'no_found_rows' => true,
			'ignore_sticky_posts' => true,
			'meta_query' => function_exists('WC') && WC()->query ? WC()->query->get_meta_query() : [],
			'tax_query' => function_exists('WC') && WC()->query ? WC()->query->get_tax_query() : [],
		];

		if (function_exists('italika_product_sort_apply_to_args')) {
			$args = italika_product_sort_apply_to_args($args);
		}

		$query = new WP_Query($args);

		if (!$query->have_posts()) {
			return [];
		}

		return array_map('intval', $query->posts);
	}
}

if (!function_exists('italika_sale_products_get_total_count')) {
	function italika_sale_products_get_total_count()
	{
		if (!function_exists('wc_get_product_ids_on_sale')) {
			return 0;
		}

		$ids = wc_get_product_ids_on_sale();

		if (!$ids) {
			return 0;
		}

		$args = [
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'post__in' => array_map('intval', $ids),
			'fields' => 'ids',
			'no_found_rows' => true,
			'ignore_sticky_posts' => true,
			'meta_query' => function_exists('WC') && WC()->query ? WC()->query->get_meta_query() : [],
			'tax_query' => function_exists('WC') && WC()->query ? WC()->query->get_tax_query() : [],
		];

		$query = new WP_Query($args);

		return $query->have_posts() ? count($query->posts) : 0;
	}
}

if (!function_exists('italika_sale_products_render_cards')) {
	function italika_sale_products_render_cards($product_ids, $card_args = [])
	{
		if (!$product_ids || !is_array($product_ids)) {
			return '';
		}

		$html = '';

		foreach ($product_ids as $product_id) {
			$html .= italika_ecomcard_render((int) $product_id, $card_args);
		}

		return $html;
	}
}

if (!function_exists('italika_sale_products_render_skeletons')) {
	function italika_sale_products_render_skeletons($count = 12)
	{
		$count = max(1, (int) $count);
		$html = '';

		for ($i = 0; $i < $count; $i++) {
			$html .= '<div class="sale-products__card sale-products__card--skeleton"><span class="sale-products__skeleton-badges"><span class="sale-products__skeleton sale-products__skeleton-badge"></span></span><span class="sale-products__image-box"><span class="sale-products__skeleton sale-products__skeleton-image"></span></span><span class="sale-products__content"><span><span class="sale-products__skeleton sale-products__skeleton-title"></span><span class="sale-products__skeleton sale-products__skeleton-title"></span><span class="sale-products__skeleton sale-products__skeleton-stock"></span></span><span class="sale-products__price-block"><span class="sale-products__skeleton sale-products__skeleton-price"></span><span class="sale-products__skeleton sale-products__skeleton-old"></span><span class="sale-products__skeleton sale-products__skeleton-benefit"></span></span><span class="sale-products__skeleton sale-products__skeleton-cart"></span></span></div>';
		}

		return $html;
	}
}

if (!function_exists('italika_sale_products_section_render')) {
	function italika_sale_products_section_render($args = [], $echo = true)
	{
		$args = wp_parse_args($args, [
			'title' => 'Наши акции',
			'archive_url' => '',
			'initial_count' => 12,
			'chunk_size' => 12,
			'section_class' => '',
			'card_args' => [],
		]);

		$initial_count = max(1, (int) $args['initial_count']);
		$chunk_size = max(1, (int) $args['chunk_size']);
		$total_count = italika_sale_products_get_total_count();
		$product_ids = italika_sale_products_get_ids($initial_count, 0);
		$cards_html = italika_sale_products_render_cards($product_ids, (array) $args['card_args']);

		if ($total_count <= 0 || $cards_html === '') {
			return '';
		}

		$show_more = $total_count > $initial_count;
		$section_classes = trim('sale-products js-sale-products-section ' . (string) $args['section_class']);

		ob_start();
		?>
<section
	class="<?php echo esc_attr($section_classes); ?>"
	data-initial-count="<?php echo esc_attr($initial_count); ?>"
	data-chunk-size="<?php echo esc_attr($chunk_size); ?>"
	data-rendered-count="<?php echo esc_attr(count($product_ids)); ?>"
	data-total-count="<?php echo esc_attr($total_count); ?>">
	<div class="container">
		<div class="sale-products__head">
			<h2 class="sale-products__title"><?php echo esc_html($args['title']); ?></h2>
			<?php if ($args['archive_url'] !== '') : ?>
				<a class="sale-products__link" href="<?php echo esc_url($args['archive_url']); ?>">Перейти в акции</a>
			<?php endif; ?>
		</div>

		<div class="sale-products__grid js-sale-products-grid"><?php echo $cards_html; ?></div>

		<?php if ($show_more) : ?>
			<div class="sale-products__actions">
				<button class="sale-products__more js-sale-products-more" type="button">
					<span class="sale-products__more-text">Загрузить еще</span>
					<span class="sale-products__more-spinner" aria-hidden="true"></span>
				</button>
			</div>
		<?php endif; ?>
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

if (!function_exists('italika_sale_products_ajax_load_more')) {
	function italika_sale_products_ajax_load_more()
	{
		check_ajax_referer('italika_sale_products_nonce', 'nonce');

		if (function_exists('italika_spam_protect_ajax_request')) {
			italika_spam_protect_ajax_request('sale_products_load_more', 20, 300);
		}

		$offset = isset($_POST['offset']) ? (int) $_POST['offset'] : 0;
		$limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 12;

		$offset = max(0, $offset);
		$limit = max(1, $limit);

		$product_ids = italika_sale_products_get_ids($limit, $offset);
		$total_count = italika_sale_products_get_total_count();
		$html = italika_sale_products_render_cards($product_ids);

		wp_send_json_success([
			'html' => $html,
			'count' => count($product_ids),
			'nextOffset' => $offset + count($product_ids),
			'hasMore' => ($offset + count($product_ids)) < $total_count,
		]);
	}
}

add_action('wp_ajax_italika_sale_products_load_more', 'italika_sale_products_ajax_load_more');
add_action('wp_ajax_nopriv_italika_sale_products_load_more', 'italika_sale_products_ajax_load_more');
