<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_catalog_get_url')) {
	function italika_catalog_get_url($category = '')
	{
		$url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/');
		$url = $url ? $url : home_url('/catalog/');
		$category = sanitize_title((string) $category);

		return $category !== '' ? add_query_arg('category', $category, $url) : $url;
	}
}

if (!function_exists('italika_catalog_get_active_category')) {
	function italika_catalog_get_active_category()
	{
		return isset($_GET['category']) ? sanitize_title(wp_unslash($_GET['category'])) : '';
	}
}

if (!function_exists('italika_catalog_get_terms_tree')) {
	function italika_catalog_get_terms_tree()
	{
		$terms = get_terms([
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
			'orderby' => 'menu_order',
			'order' => 'ASC',
		]);

		if (is_wp_error($terms) || !is_array($terms)) {
			return [];
		}

		$children = [];

		foreach ($terms as $term) {
			$parent_id = (int) $term->parent;

			if (!isset($children[$parent_id])) {
				$children[$parent_id] = [];
			}

			$children[$parent_id][] = $term;
		}

		return $children;
	}
}

if (!function_exists('italika_catalog_get_active_ancestor_ids')) {
	function italika_catalog_get_active_ancestor_ids($active_term)
	{
		$ancestor_ids = [];

		if (!$active_term || is_wp_error($active_term) || empty($active_term->term_id)) {
			return $ancestor_ids;
		}

		$parent_id = (int) $active_term->parent;

		while ($parent_id > 0) {
			$ancestor_ids[] = $parent_id;
			$parent = get_term($parent_id, 'product_cat');

			if (!$parent || is_wp_error($parent)) {
				break;
			}

			$parent_id = (int) $parent->parent;
		}

		return $ancestor_ids;
	}
}

if (!function_exists('italika_catalog_render_term_items')) {
	function italika_catalog_render_term_items($children_map, $parent_id = 0, $active_slug = '', $depth = 0, $active_ancestor_ids = [])
	{
		if (empty($children_map[$parent_id]) || $depth > 20) {
			return '';
		}

		$html = '';

		foreach ($children_map[$parent_id] as $term) {
			$term_id = (int) $term->term_id;
			$has_children = !empty($children_map[$term_id]);
			$is_current = $active_slug === $term->slug;
			$is_ancestor = in_array($term_id, $active_ancestor_ids, true);
			$item_class = $depth === 0 ? 'catalog-page__cat-item' : 'catalog-page__subcat-item';
			$link_class = $depth === 0 ? 'catalog-page__cat-link' : 'catalog-page__subcat-link';
			$children_html = $has_children ? italika_catalog_render_term_items($children_map, $term_id, $active_slug, $depth + 1, $active_ancestor_ids) : '';
			$classes = [
				$item_class,
				'catalog-page__cat-depth-' . (int) $depth,
			];

			if ($has_children && $children_html !== '') {
				$classes[] = 'catalog-page__cat-item--has-children';
			}

			if ($is_current) {
				$classes[] = 'is-current';
			}

			if ($is_ancestor || ($is_current && $has_children)) {
				$classes[] = 'is-open';
			}

			$html .= '<div class="' . esc_attr(implode(' ', $classes)) . '">';
			$html .= '<a class="' . esc_attr($link_class) . '" href="' . esc_url(italika_catalog_get_url($term->slug)) . '"><span>' . esc_html($term->name) . '</span></a>';

			if ($has_children && $children_html !== '') {
				$html .= '<button class="catalog-page__cat-toggle" type="button" aria-expanded="' . esc_attr(($is_ancestor || ($is_current && $has_children)) ? 'true' : 'false') . '" aria-label="' . esc_attr(sprintf('Показать подкатегории: %s', $term->name)) . '"></button>';
				$html .= '<div class="catalog-page__subcats catalog-page__subcats--level-' . esc_attr((string) ($depth + 1)) . '">';
				$html .= $children_html;
				$html .= '</div>';
			}

			$html .= '</div>';
		}

		return $html;
	}
}

if (!function_exists('italika_catalog_get_product_query_args')) {
	function italika_catalog_get_product_query_args($category = '', $limit = 21, $offset = 0, $with_found_rows = true)
	{
		$category = sanitize_title((string) $category);
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

		if ($category === 'sale') {
			$sale_ids = function_exists('wc_get_product_ids_on_sale') ? wc_get_product_ids_on_sale() : [];
			$sale_ids = array_values(array_unique(array_filter(array_map('intval', (array) $sale_ids))));

			if (!$sale_ids) {
				$args['post__in'] = [0];
			} else {
				$args['post__in'] = $sale_ids;
			}
		} elseif ($category !== '') {
			$args['tax_query'][] = [
				'taxonomy' => 'product_cat',
				'field' => 'slug',
				'terms' => [$category],
				'include_children' => true,
			];
		}

		return $args;
	}
}

if (!function_exists('italika_catalog_get_products')) {
	function italika_catalog_get_products($category = '', $limit = 21, $offset = 0)
	{
		$query = new WP_Query(italika_catalog_get_product_query_args($category, $limit, $offset, true));

		return [
			'ids' => $query->have_posts() ? array_map('intval', $query->posts) : [],
			'total' => (int) $query->found_posts,
		];
	}
}

if (!function_exists('italika_catalog_render_cards')) {
	function italika_catalog_render_cards($product_ids)
	{
		if (!$product_ids || !is_array($product_ids)) {
			return '';
		}

		$html = '';

		foreach ($product_ids as $product_id) {
			$html .= function_exists('italika_ecomcard_render') ? italika_ecomcard_render((int) $product_id) : '';
		}

		return $html;
	}
}

if (!function_exists('italika_catalog_render_page')) {
	function italika_catalog_render_page($echo = true)
	{
		$active_category = italika_catalog_get_active_category();
		$limit = 21;
		$catalog_data = italika_catalog_get_products($active_category, $limit, 0);
		$product_ids = $catalog_data['ids'];
		$total_count = $catalog_data['total'];
		$rendered_count = count($product_ids);
		$children_map = italika_catalog_get_terms_tree();
		$active_term = $active_category && $active_category !== 'sale' ? get_term_by('slug', $active_category, 'product_cat') : null;
		$active_ancestor_ids = italika_catalog_get_active_ancestor_ids($active_term);
		$title = 'Товары для кондитерских, пекарен и HORECA';
		$subtitle = 'Все товары';

		if ($active_category === 'sale') {
			$title = 'Акции';
			$subtitle = 'Акционные товары';
		} elseif ($active_term && !is_wp_error($active_term)) {
			$title = $active_term->name;
			$subtitle = $active_term->name;
		}

		ob_start();
		?>
<section
	class="catalog-page js-catalog-page"
	data-category="<?php echo esc_attr($active_category); ?>"
	data-rendered-count="<?php echo esc_attr($rendered_count); ?>"
	data-total-count="<?php echo esc_attr($total_count); ?>"
	data-chunk-size="<?php echo esc_attr($limit); ?>">
	<div class="container">
		<div class="catalog-page__head">
			<div class="catalog-page__intro">
				<p class="catalog-page__eyebrow">Каталог</p>
				<h1 class="catalog-page__title"><?php echo esc_html($title); ?></h1>
			</div>
			<p class="catalog-page__text">Выберите категорию слева или смотрите все позиции.</p>
		</div>

		<button class="catalog-page__rail-toggle js-catalog-menu-open" type="button" aria-controls="catalog-category-drawer" aria-expanded="false">
			<span>Категории</span>
		</button>
		<button class="catalog-page__drawer-backdrop js-catalog-menu-close" type="button" aria-label="Закрыть категории"></button>

		<div class="catalog-page__layout">
			<aside id="catalog-category-drawer" class="catalog-page__aside" aria-label="Категории каталога">
				<div class="catalog-page__aside-head">
					<h2 class="catalog-page__aside-title">Категории</h2>
					<button class="catalog-page__drawer-close js-catalog-menu-close" type="button" aria-label="Закрыть категории"></button>
				</div>

				<div class="catalog-page__nav">
					<div class="catalog-page__cat-list">
						<div class="catalog-page__cat-item catalog-page__cat-item--sale<?php echo $active_category === 'sale' ? ' is-current' : ''; ?>">
							<a class="catalog-page__cat-link" href="<?php echo esc_url(italika_catalog_get_url('sale')); ?>">
								<span>Акции</span>
							</a>
						</div>

						<?php echo italika_catalog_render_term_items($children_map, 0, $active_category, 0, $active_ancestor_ids); ?>
					</div>
				</div>
			</aside>

			<div class="catalog-page__content">
				<div class="catalog-page__toolbar">
					<div class="catalog-page__toolbar-main">
						<button class="catalog-page__mobile-categories js-catalog-menu-open" type="button" aria-controls="catalog-category-drawer" aria-expanded="false">
							<span>Категории</span>
						</button>
						<h2 class="catalog-page__subtitle"><?php echo esc_html($subtitle); ?></h2>
					</div>
					<span class="catalog-page__count js-catalog-count">
						<?php echo esc_html(sprintf('Показано %d из %d', $rendered_count, $total_count)); ?>
					</span>
				</div>

				<?php if ($rendered_count > 0) : ?>
					<div class="catalog-page__grid sale-products__grid js-catalog-grid"><?php echo italika_catalog_render_cards($product_ids); ?></div>
				<?php else : ?>
					<div class="catalog-page__empty">
						<h2 class="catalog-page__empty-title">Товары не найдены</h2>
						<p class="catalog-page__empty-text">В этой категории пока нет опубликованных товаров.</p>
					</div>
					<div class="catalog-page__grid sale-products__grid js-catalog-grid"></div>
				<?php endif; ?>

				<?php if ($total_count > $rendered_count) : ?>
					<div class="catalog-page__actions sale-products__actions">
						<button class="catalog-page__more sale-products__more js-catalog-more" type="button">
							<span class="catalog-page__more-text sale-products__more-text">Загрузить еще 21 товар</span>
							<span class="sale-products__more-spinner" aria-hidden="true"></span>
						</button>
					</div>
				<?php endif; ?>
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

if (!function_exists('italika_catalog_ajax_load_more')) {
	function italika_catalog_ajax_load_more()
	{
		check_ajax_referer('italika_catalog_nonce', 'nonce');

		if (function_exists('italika_spam_protect_ajax_request')) {
			italika_spam_protect_ajax_request('catalog_load_more', 24, 300);
		}

		$category = isset($_POST['category']) ? sanitize_title(wp_unslash($_POST['category'])) : '';
		$offset = isset($_POST['offset']) ? (int) $_POST['offset'] : 0;
		$limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 21;

		$data = italika_catalog_get_products($category, $limit, $offset);
		$html = italika_catalog_render_cards($data['ids']);
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

add_action('wp_ajax_italika_catalog_load_more', 'italika_catalog_ajax_load_more');
add_action('wp_ajax_nopriv_italika_catalog_load_more', 'italika_catalog_ajax_load_more');
