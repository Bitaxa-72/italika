<?php

defined('ABSPATH') || exit;

if (!function_exists('italika_posts_archive_get_post_type')) {
	function italika_posts_archive_get_post_type($archive_type)
	{
		$archive_type = (string) $archive_type;

		if ($archive_type === 'recipes') {
			return 'recipe';
		}

		return 'post';
	}
}

if (!function_exists('italika_posts_archive_get_url')) {
	function italika_posts_archive_get_url($archive_type)
	{
		return $archive_type === 'recipes' ? home_url('/recipes/') : home_url('/news/');
	}
}

if (!function_exists('italika_posts_archive_has_published_posts')) {
	function italika_posts_archive_has_published_posts($archive_type)
	{
		$post_type = italika_posts_archive_get_post_type($archive_type);
		$count = wp_count_posts($post_type);

		return !empty($count->publish);
	}
}

if (!function_exists('italika_posts_archive_get_type_by_url')) {
	function italika_posts_archive_get_type_by_url($url)
	{
		$path = wp_parse_url((string) $url, PHP_URL_PATH);

		if ($path === null || $path === false) {
			return '';
		}

		$path = trim((string) $path, '/');
		$home_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
		$home_path = trim((string) $home_path, '/');

		if ($home_path !== '' && ($path === $home_path || strpos($path, $home_path . '/') === 0)) {
			$path = trim(substr($path, strlen($home_path)), '/');
		}

		if ($path === 'recipes') {
			return 'recipes';
		}

		if ($path === 'news') {
			return 'news';
		}

		return '';
	}
}

if (!function_exists('italika_posts_archive_should_hide_link')) {
	function italika_posts_archive_should_hide_link($url)
	{
		$archive_type = italika_posts_archive_get_type_by_url($url);

		return $archive_type !== '' && !italika_posts_archive_has_published_posts($archive_type);
	}
}

if (!function_exists('italika_posts_archive_filter_header_menu_items')) {
	function italika_posts_archive_filter_header_menu_items($items, $args)
	{
		if (empty($args->theme_location) || $args->theme_location !== 'header_menu') {
			return $items;
		}

		$items = array_values(array_filter((array) $items, static function ($item) {
			$url = isset($item->url) ? $item->url : '';

			return !italika_posts_archive_should_hide_link($url);
		}));

		$clean_items = [];

		foreach ($items as $item) {
			$classes = isset($item->classes) && is_array($item->classes) ? $item->classes : [];
			$is_divider = in_array('menu-divider', $classes, true);
			$previous = end($clean_items);
			$previous_classes = $previous && isset($previous->classes) && is_array($previous->classes) ? $previous->classes : [];

			if ($is_divider && (empty($clean_items) || in_array('menu-divider', $previous_classes, true))) {
				continue;
			}

			$clean_items[] = $item;
		}

		while (!empty($clean_items)) {
			$last = end($clean_items);
			$classes = isset($last->classes) && is_array($last->classes) ? $last->classes : [];

			if (!in_array('menu-divider', $classes, true)) {
				break;
			}

			array_pop($clean_items);
		}

		return $clean_items;
	}

	add_filter('wp_nav_menu_objects', 'italika_posts_archive_filter_header_menu_items', 10, 2);
}

if (!function_exists('italika_posts_archive_get_labels')) {
	function italika_posts_archive_get_labels($archive_type)
	{
		if ($archive_type === 'recipes') {
			return [
				'title' => 'Рецепты',
				'all' => 'Все рецепты',
				'empty_title' => 'Рецепты не найдены',
				'empty_text' => 'Выберите другую категорию или сбросьте фильтр.',
				'aside' => 'Категории рецептов',
				'more' => 'Загрузить еще',
				'loading' => 'Загрузка',
				'subscribe_title' => 'Новости и рецепты на почту',
				'subscribe_text' => 'Отправляем полезные подборки и обновления сайта.',
				'subscribe_button' => 'Подписаться',
			];
		}

		return [
			'title' => 'Новости',
			'all' => 'Все новости',
			'empty_title' => 'Новости не найдены',
			'empty_text' => 'Выберите другую категорию или сбросьте фильтр.',
			'aside' => 'Категории новостей',
			'more' => 'Загрузить еще',
			'loading' => 'Загрузка',
			'subscribe_title' => 'Новости и рецепты на почту',
			'subscribe_text' => 'Отправляем полезные подборки и обновления сайта.',
			'subscribe_button' => 'Подписаться',
		];
	}
}

if (!function_exists('italika_posts_archive_get_category_terms')) {
	function italika_posts_archive_get_category_terms($post_type)
	{
		$terms = get_terms([
			'taxonomy' => 'category',
			'hide_empty' => true,
			'orderby' => 'name',
			'order' => 'ASC',
		]);

		if (is_wp_error($terms) || !is_array($terms)) {
			return [];
		}

		$result = [];

		foreach ($terms as $term) {
			$count_query = new WP_Query([
				'post_type' => $post_type,
				'post_status' => 'publish',
				'posts_per_page' => 1,
				'fields' => 'ids',
				'no_found_rows' => true,
				'ignore_sticky_posts' => true,
				'tax_query' => [
					[
						'taxonomy' => 'category',
						'field' => 'term_id',
						'terms' => [(int) $term->term_id],
						'include_children' => true,
					],
				],
			]);

			if ($count_query->have_posts()) {
				$result[] = $term;
			}

			wp_reset_postdata();
		}

		return $result;
	}
}

if (!function_exists('italika_posts_archive_get_query_args')) {
	function italika_posts_archive_get_query_args($post_type, $category = '', $limit = 12, $offset = 0, $with_found_rows = true)
	{
		$category = sanitize_title((string) $category);

		$args = [
			'post_type' => $post_type,
			'post_status' => 'publish',
			'posts_per_page' => max(1, (int) $limit),
			'offset' => max(0, (int) $offset),
			'orderby' => 'date',
			'order' => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows' => !$with_found_rows,
		];

		if ($category !== '') {
			$args['tax_query'] = [
				[
					'taxonomy' => 'category',
					'field' => 'slug',
					'terms' => [$category],
					'include_children' => true,
				],
			];
		}

		return $args;
	}
}

if (!function_exists('italika_posts_archive_get_posts')) {
	function italika_posts_archive_get_posts($post_type, $category = '', $limit = 12, $offset = 0)
	{
		$query = new WP_Query(italika_posts_archive_get_query_args($post_type, $category, $limit, $offset, true));
		$ids = [];

		if ($query->have_posts()) {
			$ids = array_map('intval', wp_list_pluck($query->posts, 'ID'));
		}

		wp_reset_postdata();

		return [
			'ids' => $ids,
			'total' => (int) $query->found_posts,
		];
	}
}

if (!function_exists('italika_posts_archive_get_term_name')) {
	function italika_posts_archive_get_term_name($post_id, $fallback)
	{
		$terms = get_the_terms($post_id, 'category');

		if (!empty($terms) && !is_wp_error($terms)) {
			return $terms[0]->name;
		}

		return $fallback;
	}
}

if (!function_exists('italika_posts_archive_get_excerpt')) {
	function italika_posts_archive_get_excerpt($post_id)
	{
		$excerpt = get_the_excerpt($post_id);

		if ($excerpt === '') {
			$excerpt = wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $post_id)), 26);
		}

		return $excerpt;
	}
}

if (!function_exists('italika_posts_archive_render_image')) {
	function italika_posts_archive_render_image($post_id, $class_name, $size = 'medium_large')
	{
		if (has_post_thumbnail($post_id)) {
			return get_the_post_thumbnail($post_id, $size, [
				'class' => $class_name,
				'loading' => 'lazy',
				'decoding' => 'async',
			]);
		}

		$title = get_the_title($post_id);
		$src = get_theme_file_uri('/assets/static/img/2.jpg');

		return '<img class="' . esc_attr($class_name) . '" src="' . esc_url($src) . '" alt="' . esc_attr($title) . '" loading="lazy" decoding="async">';
	}
}

if (!function_exists('italika_posts_archive_render_card')) {
	function italika_posts_archive_render_card($post_id, $archive_type, $is_featured = false)
	{
		$post_id = (int) $post_id;
		$title = get_the_title($post_id);
		$url = get_permalink($post_id);
		$fallback_category = $archive_type === 'recipes' ? 'Рецепты' : 'Новости';
		$category = italika_posts_archive_get_term_name($post_id, $fallback_category);

		if ($archive_type === 'recipes') {
			ob_start();
			?>
<article class="recipes-page__card">
	<a class="recipes-page__media" href="<?php echo esc_url($url); ?>">
		<?php echo italika_posts_archive_render_image($post_id, 'recipes-page__image'); ?>
	</a>
	<div class="recipes-page__body">
		<div class="recipes-page__meta"><?php echo esc_html($category); ?></div>
		<h2 class="recipes-page__name">
			<a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
		</h2>
	</div>
</article>
			<?php
			return ob_get_clean();
		}

		$card_class = $is_featured ? 'news-page__card news-page__card--featured' : 'news-page__card';
		$datetime = get_the_date('Y-m-d', $post_id);
		$date = get_the_date('d F Y', $post_id);
		$excerpt = italika_posts_archive_get_excerpt($post_id);

		ob_start();
		?>
<article class="<?php echo esc_attr($card_class); ?>">
	<a class="news-page__media" href="<?php echo esc_url($url); ?>">
		<?php echo italika_posts_archive_render_image($post_id, 'news-page__image'); ?>
	</a>
	<div class="news-page__body">
		<div class="news-page__meta">
			<span><?php echo esc_html($category); ?></span>
			<time datetime="<?php echo esc_attr($datetime); ?>"><?php echo esc_html($date); ?></time>
		</div>
		<h2 class="news-page__name">
			<a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
		</h2>
		<?php if ($excerpt !== '') : ?>
			<p class="news-page__excerpt"><?php echo esc_html($excerpt); ?></p>
		<?php endif; ?>
	</div>
</article>
		<?php
		return ob_get_clean();
	}
}

if (!function_exists('italika_posts_archive_render_cards')) {
	function italika_posts_archive_render_cards($post_ids, $archive_type, $offset = 0)
	{
		$html = '';

		foreach ((array) $post_ids as $index => $post_id) {
			$html .= italika_posts_archive_render_card((int) $post_id, $archive_type, $archive_type === 'news' && (int) $offset === 0 && (int) $index === 0);
		}

		return $html;
	}
}

if (!function_exists('italika_posts_archive_render_categories')) {
	function italika_posts_archive_render_categories($terms, $archive_type, $active_category = '')
	{
		$labels = italika_posts_archive_get_labels($archive_type);
		$category_class = $archive_type === 'recipes' ? 'recipes-page__category' : 'news-page__category';
		$data_attr = $archive_type === 'recipes' ? 'data-recipes-archive-category' : 'data-news-archive-category';
		$html = '<button class="' . esc_attr($category_class . ($active_category === '' ? ' is-active' : '')) . '" type="button" ' . $data_attr . '="" aria-pressed="' . esc_attr($active_category === '' ? 'true' : 'false') . '"><span>' . esc_html($labels['all']) . '</span></button>';

		foreach ((array) $terms as $term) {
			$is_active = $active_category === $term->slug;
			$html .= '<button class="' . esc_attr($category_class . ($is_active ? ' is-active' : '')) . '" type="button" ' . $data_attr . '="' . esc_attr($term->slug) . '" aria-pressed="' . esc_attr($is_active ? 'true' : 'false') . '"><span>' . esc_html($term->name) . '</span></button>';
		}

		return $html;
	}
}

if (!function_exists('italika_posts_archive_render_page')) {
	function italika_posts_archive_render_page($archive_type, $echo = true)
	{
		$archive_type = $archive_type === 'recipes' ? 'recipes' : 'news';
		$post_type = italika_posts_archive_get_post_type($archive_type);
		$labels = italika_posts_archive_get_labels($archive_type);
		$limit = 12;
		$active_category = isset($_GET['category']) ? sanitize_title(wp_unslash($_GET['category'])) : '';
		$data = italika_posts_archive_get_posts($post_type, $active_category, $limit, 0);
		$post_ids = $data['ids'];
		$total_count = $data['total'];
		$rendered_count = count($post_ids);
		$terms = italika_posts_archive_get_category_terms($post_type);
		$section_class = $archive_type === 'recipes' ? 'recipes-page js-posts-archive' : 'news-page js-posts-archive';
		$category_wrapper = $archive_type === 'recipes' ? 'recipes-page__categories js-posts-archive-categories' : 'news-page__categories js-posts-archive-categories';
		$grid_class = $archive_type === 'recipes' ? 'recipes-page__grid js-posts-archive-grid' : 'news-page__grid js-posts-archive-grid';
		$button_class = $archive_type === 'recipes' ? 'recipes-page__more js-posts-archive-more' : 'news-page__more js-posts-archive-more';
		$button_text_class = $archive_type === 'recipes' ? 'recipes-page__more-text' : 'news-page__more-text';
		$spinner_class = $archive_type === 'recipes' ? 'recipes-page__more-spinner' : 'news-page__more-spinner';
		$empty_class = $archive_type === 'recipes' ? 'recipes-page__empty js-posts-archive-empty' : 'news-page__empty js-posts-archive-empty';

		ob_start();
		?>
<section
	class="<?php echo esc_attr($section_class); ?>"
	data-posts-archive-type="<?php echo esc_attr($archive_type); ?>"
	data-posts-archive-category="<?php echo esc_attr($active_category); ?>"
	data-rendered-count="<?php echo esc_attr($rendered_count); ?>"
	data-total-count="<?php echo esc_attr($total_count); ?>"
	data-chunk-size="<?php echo esc_attr($limit); ?>">
	<div class="container">
		<div class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__head' : 'news-page__head'); ?>">
			<div class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__intro' : 'news-page__intro'); ?>">
				<h1 class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__title' : 'news-page__title'); ?>"><?php echo esc_html($labels['title']); ?></h1>
			</div>

			<div class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__subscribe' : 'news-page__subscribe'); ?>">
				<div>
					<h2 class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__subscribe-title' : 'news-page__subscribe-title'); ?>"><?php echo esc_html($labels['subscribe_title']); ?></h2>
					<p class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__subscribe-text' : 'news-page__subscribe-text'); ?>"><?php echo esc_html($labels['subscribe_text']); ?></p>
				</div>
				<button class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__subscribe-button' : 'news-page__subscribe-button'); ?>" type="button" data-subscribe-open>
					<svg viewBox="0 0 24 24" fill="none">
						<path d="M4.5 6.5h15v11h-15v-11Z" stroke-width="1.8" stroke-linejoin="round"></path>
						<path d="m5 7 7 6 7-6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
					<span><?php echo esc_html($labels['subscribe_button']); ?></span>
				</button>
			</div>
		</div>

		<div class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__layout' : 'news-page__layout'); ?>">
			<aside class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__aside' : 'news-page__aside'); ?>" aria-label="<?php echo esc_attr($labels['aside']); ?>">
				<div class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__filter' : 'news-page__filter'); ?>">
					<div class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__filter-title' : 'news-page__filter-title'); ?>">Категории</div>
					<div class="<?php echo esc_attr($category_wrapper); ?>">
						<?php echo italika_posts_archive_render_categories($terms, $archive_type, $active_category); ?>
					</div>
				</div>
			</aside>

			<div class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__content' : 'news-page__content'); ?>">
				<?php if ($archive_type === 'news') : ?>
					<article class="news-page__featured js-posts-archive-featured">
						<?php echo isset($post_ids[0]) ? italika_posts_archive_render_card((int) $post_ids[0], $archive_type, true) : ''; ?>
					</article>
					<div class="<?php echo esc_attr($grid_class); ?>" aria-live="polite">
						<?php echo italika_posts_archive_render_cards(array_slice($post_ids, 1), $archive_type, 1); ?>
					</div>
				<?php else : ?>
					<div class="<?php echo esc_attr($grid_class); ?>" aria-live="polite">
						<?php echo italika_posts_archive_render_cards($post_ids, $archive_type, 0); ?>
					</div>
				<?php endif; ?>

				<div class="<?php echo esc_attr($empty_class); ?>"<?php echo $total_count > 0 ? ' hidden' : ''; ?>>
					<h2 class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__empty-title' : 'news-page__empty-title'); ?>"><?php echo esc_html($labels['empty_title']); ?></h2>
					<p class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__empty-text' : 'news-page__empty-text'); ?>"><?php echo esc_html($labels['empty_text']); ?></p>
				</div>

				<div class="<?php echo esc_attr($archive_type === 'recipes' ? 'recipes-page__actions' : 'news-page__actions'); ?>"<?php echo $total_count > $rendered_count ? '' : ' hidden'; ?>>
					<button class="<?php echo esc_attr($button_class); ?>" type="button">
						<span class="<?php echo esc_attr($button_text_class); ?>"><?php echo esc_html($labels['more']); ?></span>
						<span class="<?php echo esc_attr($spinner_class); ?>" aria-hidden="true"></span>
					</button>
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

if (!function_exists('italika_posts_archive_ajax_load')) {
	function italika_posts_archive_ajax_load()
	{
		check_ajax_referer('italika_posts_archive_nonce', 'nonce');

		if (function_exists('italika_spam_protect_ajax_request')) {
			italika_spam_protect_ajax_request('posts_archive_load', 20, 300);
		}

		$archive_type = isset($_POST['archive_type']) && wp_unslash($_POST['archive_type']) === 'recipes' ? 'recipes' : 'news';
		$category = isset($_POST['category']) ? sanitize_title(wp_unslash($_POST['category'])) : '';
		$offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
		$limit = isset($_POST['limit']) ? max(1, (int) $_POST['limit']) : 12;
		$post_type = italika_posts_archive_get_post_type($archive_type);
		$data = italika_posts_archive_get_posts($post_type, $category, $limit, $offset);
		$ids = $data['ids'];
		$next_offset = $offset + count($ids);

		wp_send_json_success([
			'html' => italika_posts_archive_render_cards($ids, $archive_type, $offset),
			'count' => count($ids),
			'nextOffset' => $next_offset,
			'totalCount' => $data['total'],
			'hasMore' => $next_offset < $data['total'],
			'isFirstPage' => $offset === 0,
		]);
	}
}

add_action('wp_ajax_italika_posts_archive_load', 'italika_posts_archive_ajax_load');
add_action('wp_ajax_nopriv_italika_posts_archive_load', 'italika_posts_archive_ajax_load');

if (!function_exists('italika_posts_archive_is_request')) {
	function italika_posts_archive_is_request($slug)
	{
		global $wp;

		$slug = trim((string) $slug, '/');
		$request = isset($wp->request) ? trim((string) $wp->request, '/') : '';

		return is_page($slug) || $request === $slug;
	}
}

if (!function_exists('italika_posts_archive_mark_as_found')) {
	function italika_posts_archive_mark_as_found()
	{
		global $wp_query;

		if ($wp_query) {
			$wp_query->is_404 = false;
		}

		status_header(200);
	}
}

add_filter('template_include', function ($template) {
	if (italika_posts_archive_is_request('news')) {
		$news_template = get_theme_file_path('/archive-news.php');

		if (file_exists($news_template)) {
			italika_posts_archive_mark_as_found();
			return $news_template;
		}

		return $template;
	}

	if (italika_posts_archive_is_request('recipes')) {
		$recipes_template = get_theme_file_path('/archive-recipe.php');

		if (file_exists($recipes_template)) {
			italika_posts_archive_mark_as_found();
			return $recipes_template;
		}

		return $template;
	}

	return $template;
});

if (!function_exists('italika_posts_single_render_gallery')) {
	function italika_posts_single_render_gallery($post_id, $archive_type)
	{
		$attachments = get_children([
			'post_parent' => (int) $post_id,
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'orderby' => 'menu_order ID',
			'order' => 'ASC',
			'numberposts' => 12,
		]);

		if (!$attachments || !is_array($attachments)) {
			return '';
		}

		$group = $archive_type === 'recipes' ? 'post-recipe-gallery' : 'post-news-gallery';
		$title = $archive_type === 'recipes' ? 'Галерея рецепта' : 'Галерея новости';
		$html = '<section class="post-single__gallery" aria-label="' . esc_attr($title) . '">';
		$html .= '<h2 class="post-single__gallery-title">Галерея</h2>';
		$html .= '<div class="post-single__gallery-grid">';

		foreach ($attachments as $attachment) {
			$image_url = wp_get_attachment_image_url($attachment->ID, 'large');

			if (!$image_url) {
				continue;
			}

			$caption = wp_get_attachment_caption($attachment->ID);
			$alt = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
			$alt = $alt !== '' ? $alt : get_the_title($attachment->ID);
			$html .= '<a class="post-single__gallery-item" href="' . esc_url($image_url) . '" data-fancybox="' . esc_attr($group) . '" data-caption="' . esc_attr($caption ?: $alt) . '">';
			$html .= wp_get_attachment_image($attachment->ID, 'medium_large', false, [
				'loading' => 'lazy',
				'decoding' => 'async',
				'alt' => $alt,
			]);
			$html .= '</a>';
		}

		$html .= '</div></section>';

		return $html;
	}
}

if (!function_exists('italika_posts_single_render')) {
	function italika_posts_single_render($archive_type)
	{
		$archive_type = $archive_type === 'recipes' ? 'recipes' : 'news';
		$back_url = italika_posts_archive_get_url($archive_type);
		$back_text = $archive_type === 'recipes' ? 'Все рецепты' : 'Все новости';
		$single_class = $archive_type === 'recipes' ? 'post-single post-single--recipe' : 'post-single post-single--news';
		$gallery_group = $archive_type === 'recipes' ? 'post-recipe-gallery' : 'post-news-gallery';

		while (have_posts()) :
			the_post();

			$post_id = get_the_ID();
			$title = get_the_title();
			$category = italika_posts_archive_get_term_name($post_id, $archive_type === 'recipes' ? 'Рецепты' : 'Новости');
			?>
<article id="post-<?php the_ID(); ?>" <?php post_class($single_class); ?>>
	<div class="container">
		<header class="post-single__head">
			<a class="post-single__back" href="<?php echo esc_url($back_url); ?>"><?php echo esc_html($back_text); ?></a>
			<div class="post-single__meta">
				<span><?php echo esc_html($category); ?></span>
				<?php if ($archive_type === 'news') : ?>
					<time datetime="<?php echo esc_attr(get_the_date('Y-m-d')); ?>"><?php echo esc_html(get_the_date('d F Y')); ?></time>
				<?php endif; ?>
			</div>
			<h1 class="post-single__title"><?php echo esc_html($title); ?></h1>
		</header>

		<div class="post-single__content wp-content">
			<?php if (has_post_thumbnail()) : ?>
				<?php $image_url = get_the_post_thumbnail_url($post_id, 'large'); ?>
				<a class="post-single__lead-image" href="<?php echo esc_url($image_url); ?>" data-fancybox="<?php echo esc_attr($gallery_group); ?>" data-caption="<?php echo esc_attr($title); ?>">
					<?php the_post_thumbnail('large', [
						'loading' => 'eager',
						'decoding' => 'async',
						'alt' => $title,
					]); ?>
				</a>
			<?php endif; ?>

			<?php the_content(); ?>
		</div>

		<?php echo italika_posts_single_render_gallery($post_id, $archive_type); ?>
	</div>
</article>
			<?php
		endwhile;
	}
}
