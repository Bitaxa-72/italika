<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_search_get_query')) {
	function italika_search_get_query()
	{
		return isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
	}
}

if (!function_exists('italika_search_get_product_query_args')) {
	function italika_search_get_product_query_args($search = '', $limit = 12, $offset = 0, $with_found_rows = true)
	{
		$search = sanitize_text_field((string) $search);
		$limit = max(1, (int) $limit);
		$offset = max(0, (int) $offset);

		$args = [
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => $limit,
			'offset' => $offset,
			'fields' => 'ids',
			'no_found_rows' => !$with_found_rows,
			'ignore_sticky_posts' => true,
			'meta_query' => function_exists('WC') && WC()->query ? WC()->query->get_meta_query() : [],
			'tax_query' => function_exists('WC') && WC()->query ? WC()->query->get_tax_query() : [],
		];

		if (function_exists('italika_product_sort_apply_to_args')) {
			$args = italika_product_sort_apply_to_args($args);
		}

		if ($search !== '') {
			$args['s'] = $search;
		}

		return $args;
	}
}

if (!function_exists('italika_search_get_products')) {
	function italika_search_get_products($search = '', $limit = 12, $offset = 0)
	{
		$query = new WP_Query(italika_search_get_product_query_args($search, $limit, $offset, true));

		return [
			'ids' => $query->have_posts() ? array_map('intval', $query->posts) : [],
			'total' => (int) $query->found_posts,
		];
	}
}

if (!function_exists('italika_search_render_cards')) {
	function italika_search_render_cards($product_ids)
	{
		if (!$product_ids || !is_array($product_ids)) {
			return '';
		}

		$html = '';

		foreach ($product_ids as $product_id) {
			$html .= function_exists('italika_ecomcard_render')
				? italika_ecomcard_render((int) $product_id, ['card_class' => 'search-results__card'])
				: '';
		}

		return $html;
	}
}

if (!function_exists('italika_search_get_results_url')) {
	function italika_search_get_results_url($search = '')
	{
		$url = add_query_arg('post_type', 'product', home_url('/'));
		$search = sanitize_text_field((string) $search);

		return $search !== '' ? add_query_arg('s', $search, $url) : $url;
	}
}

if (!function_exists('italika_search_render_page')) {
	function italika_search_render_page($echo = true)
	{
		$search = italika_search_get_query();
		$limit = 12;
		$data = italika_search_get_products($search, $limit, 0);
		$product_ids = $data['ids'];
		$total_count = $data['total'];
		$rendered_count = count($product_ids);
		$show_more = $total_count > $rendered_count;
		$query_suffix = $search !== '' ? ' по запросу "' . $search . '"' : '';
		$summary = $total_count > 0
			? sprintf('Найдено %d товаров%s', $total_count, $query_suffix)
			: sprintf('Нет товаров%s', $query_suffix);

		ob_start();
		?>
<section
	class="search-results js-search-results-page"
	data-query="<?php echo esc_attr($search); ?>"
	data-rendered-count="<?php echo esc_attr($rendered_count); ?>"
	data-total-count="<?php echo esc_attr($total_count); ?>"
	data-chunk-size="<?php echo esc_attr($limit); ?>">
	<div class="container">
		<div class="search-results__head">
			<div class="search-results__intro">
				<h1 class="search-results__title">Результаты поиска</h1>
				<p class="search-results__summary js-search-results-summary"><?php echo esc_html($summary); ?></p>
			</div>
		</div>

		<?php if ($rendered_count > 0) : ?>
			<div class="sale-products__grid search-results__grid js-search-results-grid"><?php echo italika_search_render_cards($product_ids); ?></div>
		<?php else : ?>
			<div class="sale-products__grid search-results__grid js-search-results-grid"></div>
		<?php endif; ?>

		<div class="search-results__empty js-search-results-empty" <?php echo $rendered_count > 0 ? 'hidden' : ''; ?>>
			<h2 class="search-results__empty-title">Ничего не найдено</h2>
			<p class="search-results__empty-text">Попробуйте изменить запрос или посмотреть популярные категории.</p>
		</div>

		<?php if ($show_more) : ?>
			<div class="sale-products__actions search-results__actions">
				<button class="sale-products__more js-search-results-more" type="button">
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

if (!function_exists('italika_search_ajax_load_more')) {
	function italika_search_ajax_load_more()
	{
		check_ajax_referer('italika_search_nonce', 'nonce');

		if (function_exists('italika_spam_protect_ajax_request')) {
			italika_spam_protect_ajax_request('search_load_more', 20, 300);
		}

		$search = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';
		$offset = isset($_POST['offset']) ? (int) $_POST['offset'] : 0;
		$limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 12;

		$data = italika_search_get_products($search, $limit, $offset);
		$html = italika_search_render_cards($data['ids']);
		$next_offset = $offset + count($data['ids']);

		wp_send_json_success([
			'html' => $html,
			'count' => count($data['ids']),
			'nextOffset' => $next_offset,
			'totalCount' => $data['total'],
			'hasMore' => $next_offset < $data['total'],
		]);
	}
}

if (!function_exists('italika_search_ajax_suggest')) {
	function italika_search_ajax_suggest()
	{
		check_ajax_referer('italika_search_nonce', 'nonce');

		if (function_exists('italika_spam_protect_ajax_request')) {
			italika_spam_protect_ajax_request('search_suggest', 40, 300);
		}

		$search = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';

		$search_length = function_exists('mb_strlen') ? mb_strlen(trim($search)) : strlen(trim($search));

		if ($search_length < 3) {
			wp_send_json_success([
				'items' => [],
				'resultsUrl' => italika_search_get_results_url($search),
			]);
		}

		$data = italika_search_get_products($search, 5, 0);
		$items = [];

		foreach ($data['ids'] as $product_id) {
			$items[] = [
				'title' => get_the_title($product_id),
				'url' => get_permalink($product_id),
			];
		}

		wp_send_json_success([
			'items' => $items,
			'resultsUrl' => italika_search_get_results_url($search),
		]);
	}
}

add_action('wp_ajax_italika_search_load_more', 'italika_search_ajax_load_more');
add_action('wp_ajax_nopriv_italika_search_load_more', 'italika_search_ajax_load_more');
add_action('wp_ajax_italika_search_suggest', 'italika_search_ajax_suggest');
add_action('wp_ajax_nopriv_italika_search_suggest', 'italika_search_ajax_suggest');
