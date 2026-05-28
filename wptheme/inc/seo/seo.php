<?php
defined('ABSPATH') || exit;

if (!defined('ITALIKA_SEO_OPTION')) {
	define('ITALIKA_SEO_OPTION', 'italika_seo_core');
}

if (!function_exists('italika_seo_defaults')) {
	function italika_seo_defaults() {
		return [
			'brand_name' => 'Italika',
			'title_suffix' => 'Italika',
			'canonical_base_url' => '',
			'default_description' => 'Italika - интернет-магазин ингредиентов, инвентаря и решений для кондитеров, пекарен, кафе и HoReCa.',
			'home_title' => 'Italika - товары для кондитеров, пекарен и HoReCa',
			'home_description' => 'Ингредиенты, инвентарь и готовые решения для кондитерских, пекарен, кафе, ресторанов и домашнего творчества.',
			'catalog_title' => 'Каталог товаров для кондитеров, пекарен и HoReCa',
			'catalog_description' => 'Каталог Italika: ингредиенты, декор, упаковка, инвентарь и профессиональные товары для кондитерских, пекарен и HoReCa.',
			'news_title' => 'Новости Italika',
			'news_description' => 'Новости, обновления ассортимента, идеи и полезные материалы Italika.',
			'recipes_title' => 'Рецепты Italika',
			'recipes_description' => 'Рецепты, идеи оформления и практичные решения для кондитеров, пекарей и HoReCa.',
			'not_found_title' => 'Страница не найдена',
			'not_found_description' => 'Страница не найдена. Перейдите в каталог, новости, рецепты или вернитесь на главную Italika.',
			'og_image_id' => 0,
			'organization_phone' => '+7 (343) 210-88-95',
			'organization_email' => '',
			'organization_address' => 'Екатеринбург',
			'organization_logo_id' => 0,
			'enable_breadcrumbs' => 1,
			'enable_sitemap' => 1,
			'robots_extra' => '',
		];
	}
}

if (!function_exists('italika_seo_options')) {
	function italika_seo_options() {
		$options = get_option(ITALIKA_SEO_OPTION, []);

		return wp_parse_args(is_array($options) ? $options : [], italika_seo_defaults());
	}
}

if (!function_exists('italika_seo_option')) {
	function italika_seo_option($key, $fallback = '') {
		$options = italika_seo_options();

		return array_key_exists($key, $options) ? $options[$key] : $fallback;
	}
}

if (!function_exists('italika_seo_clean_text')) {
	function italika_seo_clean_text($value, $limit = 0) {
		$value = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $value)));

		if ($limit > 0 && function_exists('mb_strlen') && mb_strlen($value) > $limit) {
			$value = rtrim(mb_substr($value, 0, $limit - 1), " \t\n\r\0\x0B.,;:") . '…';
		} elseif ($limit > 0 && strlen($value) > $limit) {
			$value = rtrim(substr($value, 0, $limit - 1), " \t\n\r\0\x0B.,;:") . '...';
		}

		return $value;
	}
}

if (!function_exists('italika_seo_abs_url')) {
	function italika_seo_abs_url($url = '') {
		$url = (string) $url;

		if ($url === '') {
			$url = home_url('/');
		}

		$base = trim((string) italika_seo_option('canonical_base_url'));

		if ($base === '') {
			return $url;
		}

		$base = untrailingslashit($base);
		$path = wp_parse_url($url, PHP_URL_PATH);
		$query = wp_parse_url($url, PHP_URL_QUERY);
		$fragment = wp_parse_url($url, PHP_URL_FRAGMENT);
		$result = $base . '/' . ltrim((string) $path, '/');

		if ($query) {
			$result .= '?' . $query;
		}

		if ($fragment) {
			$result .= '#' . $fragment;
		}

		return $result;
	}
}

if (!function_exists('italika_seo_current_url')) {
	function italika_seo_current_url() {
		global $wp;

		$path = isset($wp->request) ? $wp->request : '';
		$url = home_url($path ? '/' . $path . '/' : '/');

		$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
		if ($paged > 1) {
			$url = trailingslashit($url) . 'page/' . $paged . '/';
		}

		return italika_seo_abs_url($url);
	}
}

if (!function_exists('italika_seo_is_catalog_query_page')) {
	function italika_seo_is_catalog_query_page() {
		return function_exists('is_shop') && is_shop();
	}
}

if (!function_exists('italika_seo_active_catalog_term')) {
	function italika_seo_active_catalog_term() {
		if (!italika_seo_is_catalog_query_page() || empty($_GET['category'])) {
			return null;
		}

		$slug = sanitize_title(wp_unslash($_GET['category']));

		if ($slug === '' || $slug === 'sale') {
			return null;
		}

		$term = get_term_by('slug', $slug, 'product_cat');

		return $term && !is_wp_error($term) ? $term : null;
	}
}

if (!function_exists('italika_seo_is_service_page')) {
	function italika_seo_is_service_page() {
		if (is_search() || is_404()) {
			return true;
		}

		if (function_exists('is_cart') && is_cart()) {
			return true;
		}

		if (function_exists('is_checkout') && is_checkout()) {
			return true;
		}

		if (function_exists('is_account_page') && is_account_page()) {
			return true;
		}

		if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url()) {
			return true;
		}

		$page_path = trim((string) get_query_var('pagename'), '/');

		return in_array($page_path, ['favorites'], true) || italika_seo_is_favorites_page();
	}
}

if (!function_exists('italika_seo_is_favorites_page')) {
	function italika_seo_is_favorites_page() {
		if (function_exists('italika_favorites_is_archive_request') && italika_favorites_is_archive_request()) {
			return true;
		}

		return (string) get_query_var('italika_favorites') === '1';
	}
}

if (!function_exists('italika_seo_meta_value')) {
	function italika_seo_meta_value($key, $post_id = 0) {
		$post_id = $post_id ? (int) $post_id : get_queried_object_id();

		return $post_id ? trim((string) get_post_meta($post_id, '_italika_seo_' . $key, true)) : '';
	}
}

if (!function_exists('italika_seo_image_url')) {
	function italika_seo_image_url($context_post_id = 0) {
		$context_post_id = $context_post_id ? (int) $context_post_id : get_queried_object_id();
		$override_id = (int) italika_seo_meta_value('og_image_id', $context_post_id);

		if ($override_id) {
			$url = wp_get_attachment_image_url($override_id, 'full');
			if ($url) {
				return $url;
			}
		}

		if ($context_post_id && has_post_thumbnail($context_post_id)) {
			$url = get_the_post_thumbnail_url($context_post_id, 'full');
			if ($url) {
				return $url;
			}
		}

		if (function_exists('is_product') && is_product()) {
			$product = wc_get_product($context_post_id);
			if ($product && $product->get_image_id()) {
				$url = wp_get_attachment_image_url($product->get_image_id(), 'full');
				if ($url) {
					return $url;
				}
			}
		}

		$default_id = (int) italika_seo_option('og_image_id');
		if ($default_id) {
			$url = wp_get_attachment_image_url($default_id, 'full');
			if ($url) {
				return $url;
			}
		}

		return get_theme_file_uri('/assets/static/img/2.jpg');
	}
}

if (!function_exists('italika_seo_term_image_url')) {
	function italika_seo_term_image_url($term) {
		if (!$term instanceof WP_Term) {
			return '';
		}

		$override_id = (int) get_term_meta($term->term_id, '_italika_seo_og_image_id', true);
		if ($override_id) {
			$url = wp_get_attachment_image_url($override_id, 'full');
			if ($url) {
				return $url;
			}
		}

		$thumbnail_id = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
		if ($thumbnail_id) {
			$url = wp_get_attachment_image_url($thumbnail_id, 'full');
			if ($url) {
				return $url;
			}
		}

		return '';
	}
}

if (!function_exists('italika_seo_description_from_post')) {
	function italika_seo_description_from_post($post_id) {
		$manual = italika_seo_meta_value('description', $post_id);

		if ($manual !== '') {
			return italika_seo_clean_text($manual, 170);
		}

		$excerpt = get_the_excerpt($post_id);

		if ($excerpt === '') {
			$excerpt = get_post_field('post_content', $post_id);
		}

		return italika_seo_clean_text($excerpt, 170);
	}
}

if (!function_exists('italika_seo_context')) {
	function italika_seo_context() {
		$options = italika_seo_options();
		$suffix = trim((string) $options['title_suffix']);
		$title = '';
		$description = '';
		$canonical = italika_seo_current_url();
		$type = 'website';
		$noindex = false;
		$post_id = get_queried_object_id();
		$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));

		if ((int) get_option('blog_public') !== 1) {
			$noindex = true;
		}

		if (is_front_page()) {
			$title = (string) $options['home_title'];
			$description = (string) $options['home_description'];
			$canonical = italika_seo_abs_url(home_url('/'));
		} elseif (italika_seo_is_favorites_page()) {
			$title = 'Избранное';
			$description = 'Сохраненные товары Italika в личном кабинете покупателя.';
			$canonical = italika_seo_abs_url(home_url('/favorites/'));
			$noindex = true;
		} elseif (is_404()) {
			$title = (string) $options['not_found_title'];
			$description = (string) $options['not_found_description'];
			$canonical = '';
			$noindex = true;
		} elseif (is_search()) {
			$query = italika_seo_clean_text(get_search_query(), 80);
			$title = $query ? 'Поиск: ' . $query : 'Поиск по сайту';
			$description = 'Результаты поиска по каталогу и материалам Italika.';
			$noindex = true;
		} elseif (function_exists('is_product') && is_product()) {
			$product = wc_get_product($post_id);
			$title = italika_seo_meta_value('title', $post_id);
			$title = $title !== '' ? $title : get_the_title($post_id);
			$description = italika_seo_description_from_post($post_id);
			$canonical = italika_seo_abs_url(get_permalink($post_id));
			$type = 'product';

			if ($description === '' && $product) {
				$description = italika_seo_clean_text($product->get_short_description(), 170);
			}
		} elseif (function_exists('is_product_category') && is_product_category()) {
			$term = get_queried_object();
			$title = get_term_meta($term->term_id, '_italika_seo_title', true);
			$title = $title !== '' ? $title : $term->name . ' - каталог Italika';
			$description = get_term_meta($term->term_id, '_italika_seo_description', true);
			$description = $description !== '' ? $description : term_description($term->term_id, 'product_cat');
			$description = italika_seo_clean_text($description !== '' ? $description : $options['catalog_description'], 170);
			$canonical = italika_seo_abs_url(get_term_link($term));
		} elseif (italika_seo_is_catalog_query_page()) {
			$active_term = italika_seo_active_catalog_term();
			if (!empty($_GET['category']) && sanitize_title(wp_unslash($_GET['category'])) === 'sale') {
				$title = 'Акции и скидки Italika';
				$description = 'Акционные товары и выгодные предложения Italika для кондитеров, пекарен и HoReCa.';
				$canonical = italika_seo_abs_url(add_query_arg('category', 'sale', function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/')));
			} elseif ($active_term) {
				$title = $active_term->name . ' - каталог Italika';
				$description = italika_seo_clean_text(term_description($active_term->term_id, 'product_cat') ?: $options['catalog_description'], 170);
				$canonical = italika_seo_abs_url(add_query_arg('category', $active_term->slug, function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/')));
			} else {
				$title = (string) $options['catalog_title'];
				$description = (string) $options['catalog_description'];
				$canonical = italika_seo_abs_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/'));
			}
		} elseif (is_post_type_archive('recipe')) {
			$title = (string) $options['recipes_title'];
			$description = (string) $options['recipes_description'];
			$canonical = italika_seo_abs_url(home_url('/recipes/'));
		} elseif (is_home() || is_archive() && !is_post_type_archive()) {
			$title = (string) $options['news_title'];
			$description = (string) $options['news_description'];
			$canonical = italika_seo_abs_url(home_url('/news/'));
		} elseif (is_singular('recipe')) {
			$title = italika_seo_meta_value('title', $post_id);
			$title = $title !== '' ? $title : get_the_title($post_id) . ' - рецепт Italika';
			$description = italika_seo_description_from_post($post_id);
			$canonical = italika_seo_abs_url(get_permalink($post_id));
			$type = 'article';
		} elseif (is_singular('post')) {
			$title = italika_seo_meta_value('title', $post_id);
			$title = $title !== '' ? $title : get_the_title($post_id) . ' - новости Italika';
			$description = italika_seo_description_from_post($post_id);
			$canonical = italika_seo_abs_url(get_permalink($post_id));
			$type = 'article';
		} elseif (is_singular()) {
			$title = italika_seo_meta_value('title', $post_id);
			$title = $title !== '' ? $title : get_the_title($post_id);
			$description = italika_seo_description_from_post($post_id);
			$canonical = italika_seo_abs_url(get_permalink($post_id));
		}

		if ($description === '') {
			$description = (string) $options['default_description'];
		}

		if ($paged > 1 && $title !== '') {
			$title .= ' - страница ' . $paged;
		}

		if ($suffix !== '' && $title !== '' && stripos($title, $suffix) === false) {
			$title .= ' - ' . $suffix;
		}

		if ($post_id && italika_seo_meta_value('noindex', $post_id) === '1') {
			$noindex = true;
		}

		$queried = get_queried_object();
		if ($queried instanceof WP_Term && get_term_meta($queried->term_id, '_italika_seo_noindex', true) === '1') {
			$noindex = true;
		}

		if (italika_seo_is_service_page()) {
			$noindex = true;
		}

		$canonical_override = $post_id ? italika_seo_meta_value('canonical', $post_id) : '';
		if ($canonical_override !== '') {
			$canonical = italika_seo_abs_url($canonical_override);
		}

		$og_image = $queried instanceof WP_Term ? italika_seo_term_image_url($queried) : '';
		if ($og_image === '' && isset($active_term) && $active_term instanceof WP_Term) {
			$og_image = italika_seo_term_image_url($active_term);
		}
		if ($og_image === '') {
			$og_image = italika_seo_image_url($post_id);
		}

		return [
			'title' => italika_seo_clean_text($title, 90),
			'description' => italika_seo_clean_text($description, 170),
			'canonical' => $canonical,
			'og_type' => $type,
			'og_image' => italika_seo_abs_url($og_image),
			'noindex' => $noindex,
			'post_id' => $post_id,
		];
	}
}

if (!function_exists('italika_seo_sanitize_options')) {
	function italika_seo_sanitize_options($input) {
		$defaults = italika_seo_defaults();
		$input = is_array($input) ? $input : [];
		$output = [];

		foreach ($defaults as $key => $default) {
			$value = $input[$key] ?? $default;

			if (in_array($key, ['og_image_id', 'organization_logo_id', 'enable_breadcrumbs', 'enable_sitemap'], true)) {
				$output[$key] = absint($value);
			} elseif ($key === 'robots_extra') {
				$output[$key] = sanitize_textarea_field((string) $value);
			} elseif ($key === 'canonical_base_url') {
				$output[$key] = esc_url_raw(trim((string) $value));
			} else {
				$output[$key] = sanitize_text_field((string) $value);
			}
		}

		return wp_parse_args($output, $defaults);
	}
}

if (!function_exists('italika_seo_register_admin')) {
	function italika_seo_register_admin() {
		register_setting('italika_seo_core', ITALIKA_SEO_OPTION, [
			'type' => 'array',
			'sanitize_callback' => 'italika_seo_sanitize_options',
			'default' => italika_seo_defaults(),
		]);

		add_menu_page(
			'SEO ядро',
			'SEO ядро',
			'manage_options',
			'italika-seo-core',
			'italika_seo_render_settings_page',
			'dashicons-search',
			58
		);
	}
}

add_action('admin_menu', 'italika_seo_register_admin');
add_action('admin_init', function () {
	register_setting('italika_seo_core', ITALIKA_SEO_OPTION, [
		'type' => 'array',
		'sanitize_callback' => 'italika_seo_sanitize_options',
		'default' => italika_seo_defaults(),
	]);
});

if (!function_exists('italika_seo_render_text_field')) {
	function italika_seo_render_text_field($key, $label, $type = 'text') {
		$options = italika_seo_options();
		$value = $options[$key] ?? '';
		$name = ITALIKA_SEO_OPTION . '[' . $key . ']';

		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input class="regular-text" type="%3$s" id="%1$s" name="%4$s" value="%5$s"></td></tr>',
			esc_attr($key),
			esc_html($label),
			esc_attr($type),
			esc_attr($name),
			esc_attr((string) $value)
		);
	}
}

if (!function_exists('italika_seo_render_textarea_field')) {
	function italika_seo_render_textarea_field($key, $label) {
		$options = italika_seo_options();
		$value = $options[$key] ?? '';
		$name = ITALIKA_SEO_OPTION . '[' . $key . ']';

		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><textarea class="large-text" rows="4" id="%1$s" name="%3$s">%4$s</textarea></td></tr>',
			esc_attr($key),
			esc_html($label),
			esc_attr($name),
			esc_textarea((string) $value)
		);
	}
}

if (!function_exists('italika_seo_render_checkbox_field')) {
	function italika_seo_render_checkbox_field($key, $label) {
		$options = italika_seo_options();
		$name = ITALIKA_SEO_OPTION . '[' . $key . ']';

		printf(
			'<tr><th scope="row">%1$s</th><td><label><input type="checkbox" name="%2$s" value="1" %3$s> Включено</label></td></tr>',
			esc_html($label),
			esc_attr($name),
			checked(!empty($options[$key]), true, false)
		);
	}
}

if (!function_exists('italika_seo_render_media_field')) {
	function italika_seo_render_media_field($key, $label) {
		$options = italika_seo_options();
		$value = (int) ($options[$key] ?? 0);
		$name = ITALIKA_SEO_OPTION . '[' . $key . ']';
		$image = $value ? wp_get_attachment_image($value, 'thumbnail', false, ['style' => 'max-width:120px;height:auto;display:block;margin-bottom:8px;']) : '';

		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td>%3$s<input class="small-text italika-seo-media-id" type="number" id="%1$s" name="%4$s" value="%5$d"> <button class="button italika-seo-media-button" type="button" data-target="%1$s">Выбрать</button></td></tr>',
			esc_attr($key),
			esc_html($label),
			$image,
			esc_attr($name),
			$value
		);
	}
}

if (!function_exists('italika_seo_render_settings_page')) {
	function italika_seo_render_settings_page() {
		if (!current_user_can('manage_options')) {
			return;
		}
		?>
		<div class="wrap">
			<h1>SEO ядро</h1>
			<p>Базовое SEO темы Italika: title, description, canonical, Open Graph, schema, sitemap, robots.txt и хлебные крошки.</p>

			<form method="post" action="options.php">
				<?php settings_fields('italika_seo_core'); ?>

				<h2>Основное</h2>
				<table class="form-table" role="presentation">
					<?php italika_seo_render_text_field('brand_name', 'Название бренда'); ?>
					<?php italika_seo_render_text_field('title_suffix', 'Суффикс title'); ?>
					<?php italika_seo_render_text_field('canonical_base_url', 'Базовый URL canonical, если нужен хардкод'); ?>
					<?php italika_seo_render_textarea_field('default_description', 'Описание по умолчанию'); ?>
					<?php italika_seo_render_media_field('og_image_id', 'Fallback Open Graph изображение'); ?>
				</table>

				<h2>Основные страницы</h2>
				<table class="form-table" role="presentation">
					<?php italika_seo_render_text_field('home_title', 'Title главной'); ?>
					<?php italika_seo_render_textarea_field('home_description', 'Description главной'); ?>
					<?php italika_seo_render_text_field('catalog_title', 'Title каталога'); ?>
					<?php italika_seo_render_textarea_field('catalog_description', 'Description каталога'); ?>
					<?php italika_seo_render_text_field('news_title', 'Title новостей'); ?>
					<?php italika_seo_render_textarea_field('news_description', 'Description новостей'); ?>
					<?php italika_seo_render_text_field('recipes_title', 'Title рецептов'); ?>
					<?php italika_seo_render_textarea_field('recipes_description', 'Description рецептов'); ?>
					<?php italika_seo_render_text_field('not_found_title', 'Title 404'); ?>
					<?php italika_seo_render_textarea_field('not_found_description', 'Description 404'); ?>
				</table>

				<h2>Организация</h2>
				<table class="form-table" role="presentation">
					<?php italika_seo_render_text_field('organization_phone', 'Телефон'); ?>
					<?php italika_seo_render_text_field('organization_email', 'Email', 'email'); ?>
					<?php italika_seo_render_text_field('organization_address', 'Адрес/город'); ?>
					<?php italika_seo_render_media_field('organization_logo_id', 'Логотип для schema'); ?>
				</table>

				<h2>Техническое</h2>
				<table class="form-table" role="presentation">
					<?php italika_seo_render_checkbox_field('enable_breadcrumbs', 'Хлебные крошки'); ?>
					<?php italika_seo_render_checkbox_field('enable_sitemap', 'Sitemap'); ?>
					<?php italika_seo_render_textarea_field('robots_extra', 'Дополнительные строки robots.txt'); ?>
				</table>

				<?php submit_button('Сохранить SEO ядро'); ?>
			</form>
		</div>
		<?php
	}
}

add_action('admin_enqueue_scripts', function ($hook) {
	if ($hook !== 'toplevel_page_italika-seo-core') {
		return;
	}

	wp_enqueue_media();
	wp_add_inline_script('media-editor', "
		document.addEventListener('click', function (event) {
			var button = event.target.closest('.italika-seo-media-button');
			if (!button || !window.wp || !wp.media) return;
			event.preventDefault();
			var target = document.getElementById(button.getAttribute('data-target'));
			var frame = wp.media({ title: 'Выберите изображение', multiple: false, library: { type: 'image' } });
			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				if (target) target.value = attachment.id;
			});
			frame.open();
		});
	");
});

if (!function_exists('italika_seo_add_meta_box')) {
	function italika_seo_add_meta_box() {
		$post_types = ['page', 'post', 'recipe'];

		if (post_type_exists('product')) {
			$post_types[] = 'product';
		}

		foreach ($post_types as $post_type) {
			add_meta_box('italika-seo-meta', 'SEO ядро', 'italika_seo_render_meta_box', $post_type, 'normal', 'default');
		}
	}
}

add_action('add_meta_boxes', 'italika_seo_add_meta_box');

if (!function_exists('italika_seo_render_meta_box')) {
	function italika_seo_render_meta_box($post) {
		wp_nonce_field('italika_seo_save_meta', 'italika_seo_nonce');

		$fields = [
			'title' => 'SEO title',
			'description' => 'SEO description',
			'canonical' => 'Canonical override',
			'og_image_id' => 'OG image ID',
		];
		?>
		<p>Поля необязательные. Если оставить пустыми, SEO ядро сгенерирует значения автоматически.</p>
		<?php foreach ($fields as $key => $label) : ?>
			<p>
				<label for="italika_seo_<?php echo esc_attr($key); ?>"><strong><?php echo esc_html($label); ?></strong></label><br>
				<?php if ($key === 'description') : ?>
					<textarea class="widefat" rows="3" id="italika_seo_<?php echo esc_attr($key); ?>" name="italika_seo[<?php echo esc_attr($key); ?>]"><?php echo esc_textarea(italika_seo_meta_value($key, $post->ID)); ?></textarea>
				<?php else : ?>
					<input class="widefat" type="text" id="italika_seo_<?php echo esc_attr($key); ?>" name="italika_seo[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr(italika_seo_meta_value($key, $post->ID)); ?>">
				<?php endif; ?>
			</p>
		<?php endforeach; ?>
		<p>
			<label>
				<input type="checkbox" name="italika_seo[noindex]" value="1" <?php checked(italika_seo_meta_value('noindex', $post->ID), '1'); ?>>
				Noindex для этой страницы
			</label>
		</p>
		<?php
	}
}

add_action('save_post', function ($post_id) {
	if (!isset($_POST['italika_seo_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['italika_seo_nonce'])), 'italika_seo_save_meta')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$data = isset($_POST['italika_seo']) ? wp_unslash((array) $_POST['italika_seo']) : [];
	$keys = ['title', 'description', 'canonical', 'og_image_id'];

	foreach ($keys as $key) {
		$value = isset($data[$key]) ? trim((string) $data[$key]) : '';

		if ($key === 'canonical') {
			$value = $value !== '' ? esc_url_raw($value) : '';
		} elseif ($key === 'og_image_id') {
			$value = $value !== '' ? (string) absint($value) : '';
		} else {
			$value = sanitize_text_field($value);
		}

		if ($value === '') {
			delete_post_meta($post_id, '_italika_seo_' . $key);
		} else {
			update_post_meta($post_id, '_italika_seo_' . $key, $value);
		}
	}

	if (!empty($data['noindex'])) {
		update_post_meta($post_id, '_italika_seo_noindex', '1');
	} else {
		delete_post_meta($post_id, '_italika_seo_noindex');
	}
}, 20);

add_filter('document_title_parts', function ($parts) {
	if (is_admin()) {
		return $parts;
	}

	$context = italika_seo_context();

	if (!empty($context['title'])) {
		$parts['title'] = $context['title'];
		unset($parts['site'], $parts['tagline']);
	}

	return $parts;
}, 20);

remove_action('wp_head', 'rel_canonical');

add_filter('wp_robots', function ($robots) {
	$context = italika_seo_context();

	if (!empty($context['noindex'])) {
		unset($robots['max-image-preview']);
		unset($robots['nofollow']);
		$robots['noindex'] = true;
		$robots['follow'] = true;
	} else {
		unset($robots['noindex'], $robots['nofollow']);
		$robots['index'] = true;
		$robots['follow'] = true;
		$robots['max-image-preview'] = 'large';
	}

	return $robots;
}, 20);

if (!function_exists('italika_seo_json')) {
	function italika_seo_json($data) {
		return wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}
}

if (!function_exists('italika_seo_schema_organization')) {
	function italika_seo_schema_organization() {
		$options = italika_seo_options();
		$logo_id = (int) $options['organization_logo_id'];
		$logo = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : get_theme_file_uri('/assets/static/icons/italika-logo.svg');

		$data = [
			'@type' => 'Organization',
			'@id' => italika_seo_abs_url(home_url('/#organization')),
			'name' => $options['brand_name'],
			'url' => italika_seo_abs_url(home_url('/')),
			'logo' => italika_seo_abs_url($logo),
		];

		if (!empty($options['organization_phone'])) {
			$data['telephone'] = $options['organization_phone'];
		}

		if (!empty($options['organization_email'])) {
			$data['email'] = $options['organization_email'];
		}

		if (!empty($options['organization_address'])) {
			$data['address'] = [
				'@type' => 'PostalAddress',
				'addressLocality' => $options['organization_address'],
				'addressCountry' => 'RU',
			];
		}

		return $data;
	}
}

if (!function_exists('italika_seo_schema_website')) {
	function italika_seo_schema_website() {
		return [
			'@type' => 'WebSite',
			'@id' => italika_seo_abs_url(home_url('/#website')),
			'url' => italika_seo_abs_url(home_url('/')),
			'name' => italika_seo_option('brand_name', 'Italika'),
			'publisher' => [
				'@id' => italika_seo_abs_url(home_url('/#organization')),
			],
			'potentialAction' => [
				'@type' => 'SearchAction',
				'target' => italika_seo_abs_url(home_url('/')) . '?s={search_term_string}&post_type=product',
				'query-input' => 'required name=search_term_string',
			],
		];
	}
}

if (!function_exists('italika_seo_schema_product')) {
	function italika_seo_schema_product($post_id) {
		if (!function_exists('wc_get_product')) {
			return null;
		}

		$product = wc_get_product($post_id);

		if (!$product) {
			return null;
		}

		$availability = $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
		$image = italika_seo_image_url($post_id);

		return [
			'@type' => 'Product',
			'@id' => italika_seo_abs_url(get_permalink($post_id) . '#product'),
			'name' => $product->get_name(),
			'image' => italika_seo_abs_url($image),
			'description' => italika_seo_context()['description'],
			'sku' => $product->get_sku() ?: (string) $product->get_id(),
			'brand' => [
				'@type' => 'Brand',
				'name' => italika_seo_option('brand_name', 'Italika'),
			],
			'offers' => [
				'@type' => 'Offer',
				'url' => italika_seo_abs_url(get_permalink($post_id)),
				'priceCurrency' => get_woocommerce_currency(),
				'price' => wc_format_decimal($product->get_price(), wc_get_price_decimals()),
				'availability' => $availability,
				'itemCondition' => 'https://schema.org/NewCondition',
			],
		];
	}
}

if (!function_exists('italika_seo_schema_article')) {
	function italika_seo_schema_article($post_id, $type = 'Article') {
		$image = italika_seo_image_url($post_id);

		return [
			'@type' => $type,
			'@id' => italika_seo_abs_url(get_permalink($post_id) . '#article'),
			'headline' => get_the_title($post_id),
			'description' => italika_seo_context()['description'],
			'image' => italika_seo_abs_url($image),
			'datePublished' => get_the_date(DATE_W3C, $post_id),
			'dateModified' => get_the_modified_date(DATE_W3C, $post_id),
			'author' => [
				'@type' => 'Organization',
				'name' => italika_seo_option('brand_name', 'Italika'),
			],
			'publisher' => [
				'@id' => italika_seo_abs_url(home_url('/#organization')),
			],
			'mainEntityOfPage' => italika_seo_abs_url(get_permalink($post_id)),
		];
	}
}

if (!function_exists('italika_seo_schema_collection')) {
	function italika_seo_schema_collection($context) {
		if (empty($context['canonical'])) {
			return null;
		}

		return [
			'@type' => 'CollectionPage',
			'@id' => $context['canonical'] . '#collection',
			'name' => $context['title'],
			'description' => $context['description'],
			'url' => $context['canonical'],
			'isPartOf' => [
				'@id' => italika_seo_abs_url(home_url('/#website')),
			],
		];
	}
}

if (!function_exists('italika_seo_schema_graph')) {
	function italika_seo_schema_graph($context) {
		$graph = [
			italika_seo_schema_organization(),
			italika_seo_schema_website(),
		];

		$breadcrumbs_schema = italika_seo_breadcrumb_schema();
		if ($breadcrumbs_schema) {
			$graph[] = $breadcrumbs_schema;
		}

		if (function_exists('is_product') && is_product()) {
			$product_schema = italika_seo_schema_product((int) $context['post_id']);
			if ($product_schema) {
				$graph[] = $product_schema;
			}
		} elseif (is_singular('recipe')) {
			$graph[] = italika_seo_schema_article((int) $context['post_id'], 'Article');
		} elseif (is_singular('post')) {
			$graph[] = italika_seo_schema_article((int) $context['post_id'], 'NewsArticle');
		} elseif (!is_404() && !is_search()) {
			$collection = italika_seo_schema_collection($context);
			if ($collection) {
				$graph[] = $collection;
			}
		}

		return [
			'@context' => 'https://schema.org',
			'@graph' => array_values(array_filter($graph)),
		];
	}
}

if (!function_exists('italika_seo_render_head')) {
	function italika_seo_render_head() {
		if (is_admin()) {
			return;
		}

		$context = italika_seo_context();

		if (!empty($context['description'])) {
			echo '<meta name="description" content="' . esc_attr($context['description']) . '">' . "\n";
		}

		if (!empty($context['canonical']) && !is_404() && !is_search() && !italika_seo_is_service_page()) {
			echo '<link rel="canonical" href="' . esc_url($context['canonical']) . '">' . "\n";
		}

		echo '<meta property="og:locale" content="ru_RU">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr(italika_seo_option('brand_name', 'Italika')) . '">' . "\n";
		echo '<meta property="og:type" content="' . esc_attr($context['og_type']) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr($context['title']) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr($context['description']) . '">' . "\n";

		if (!empty($context['canonical'])) {
			echo '<meta property="og:url" content="' . esc_url($context['canonical']) . '">' . "\n";
		}

		if (!empty($context['og_image'])) {
			echo '<meta property="og:image" content="' . esc_url($context['og_image']) . '">' . "\n";
		}

		if (function_exists('is_product') && is_product() && function_exists('wc_get_product')) {
			$product = wc_get_product($context['post_id']);
			if ($product) {
				echo '<meta property="product:price:amount" content="' . esc_attr(wc_format_decimal($product->get_price(), wc_get_price_decimals())) . '">' . "\n";
				echo '<meta property="product:price:currency" content="' . esc_attr(get_woocommerce_currency()) . '">' . "\n";
			}
		}

		echo '<script type="application/ld+json">' . italika_seo_json(italika_seo_schema_graph($context)) . '</script>' . "\n";
	}
}

add_action('wp_head', 'italika_seo_render_head', 2);

if (!function_exists('italika_seo_breadcrumb_items')) {
	function italika_seo_breadcrumb_items() {
		$items = [
			[
				'label' => 'Главная',
				'url' => italika_seo_abs_url(home_url('/')),
			],
		];

		if (is_front_page()) {
			return [];
		}

		if (italika_seo_is_favorites_page()) {
			$items[] = ['label' => 'Избранное', 'url' => ''];
			return $items;
		}

		if (is_404()) {
			$items[] = ['label' => 'Страница не найдена', 'url' => ''];
			return $items;
		}

		if (is_search()) {
			$items[] = ['label' => 'Поиск', 'url' => ''];
			return $items;
		}

		if (function_exists('is_cart') && is_cart()) {
			$items[] = ['label' => 'Корзина', 'url' => ''];
			return $items;
		}

		if (function_exists('is_checkout') && is_checkout()) {
			$items[] = ['label' => 'Оформление заказа', 'url' => ''];
			return $items;
		}

		if (function_exists('is_account_page') && is_account_page()) {
			$items[] = ['label' => 'Личный кабинет', 'url' => ''];
			return $items;
		}

		if (function_exists('is_product') && is_product()) {
			$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/');
			$items[] = ['label' => 'Каталог', 'url' => italika_seo_abs_url($shop_url)];

			$terms = get_the_terms(get_queried_object_id(), 'product_cat');
			if (!empty($terms) && !is_wp_error($terms)) {
				usort($terms, function ($a, $b) {
					return count(get_ancestors($b->term_id, 'product_cat')) <=> count(get_ancestors($a->term_id, 'product_cat'));
				});

				$term = $terms[0];
				$ancestors = array_reverse(get_ancestors($term->term_id, 'product_cat'));

				foreach ($ancestors as $ancestor_id) {
					$ancestor = get_term($ancestor_id, 'product_cat');
					if ($ancestor && !is_wp_error($ancestor)) {
						$items[] = ['label' => $ancestor->name, 'url' => italika_seo_abs_url(get_term_link($ancestor))];
					}
				}

				$items[] = ['label' => $term->name, 'url' => italika_seo_abs_url(get_term_link($term))];
			}

			$items[] = ['label' => get_the_title(), 'url' => ''];
			return $items;
		}

		if (function_exists('is_product_category') && is_product_category()) {
			$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/');
			$term = get_queried_object();
			$items[] = ['label' => 'Каталог', 'url' => italika_seo_abs_url($shop_url)];

			foreach (array_reverse(get_ancestors($term->term_id, 'product_cat')) as $ancestor_id) {
				$ancestor = get_term($ancestor_id, 'product_cat');
				if ($ancestor && !is_wp_error($ancestor)) {
					$items[] = ['label' => $ancestor->name, 'url' => italika_seo_abs_url(get_term_link($ancestor))];
				}
			}

			$items[] = ['label' => $term->name, 'url' => ''];
			return $items;
		}

		if (italika_seo_is_catalog_query_page()) {
			$items[] = ['label' => 'Каталог', 'url' => ''];
			$active_term = italika_seo_active_catalog_term();
			if ($active_term) {
				$items[count($items) - 1]['url'] = italika_seo_abs_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/'));
				$items[] = ['label' => $active_term->name, 'url' => ''];
			} elseif (!empty($_GET['category']) && sanitize_title(wp_unslash($_GET['category'])) === 'sale') {
				$items[count($items) - 1]['url'] = italika_seo_abs_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/'));
				$items[] = ['label' => 'Акции', 'url' => ''];
			}
			return $items;
		}

		if (is_post_type_archive('recipe')) {
			$items[] = ['label' => 'Рецепты', 'url' => ''];
			return $items;
		}

		if (is_singular('recipe')) {
			$items[] = ['label' => 'Рецепты', 'url' => italika_seo_abs_url(home_url('/recipes/'))];
			$terms = get_the_terms(get_queried_object_id(), 'category');
			if (!empty($terms) && !is_wp_error($terms)) {
				$items[] = ['label' => $terms[0]->name, 'url' => italika_seo_abs_url(get_category_link($terms[0]))];
			}
			$items[] = ['label' => get_the_title(), 'url' => ''];
			return $items;
		}

		if (is_home() || (is_archive() && !is_post_type_archive())) {
			$items[] = ['label' => 'Новости', 'url' => ''];
			return $items;
		}

		if (is_singular('post')) {
			$items[] = ['label' => 'Новости', 'url' => italika_seo_abs_url(home_url('/news/'))];
			$items[] = ['label' => get_the_title(), 'url' => ''];
			return $items;
		}

		if (is_page()) {
			$ancestors = array_reverse(get_post_ancestors(get_queried_object_id()));
			foreach ($ancestors as $ancestor_id) {
				$items[] = [
					'label' => get_the_title($ancestor_id),
					'url' => italika_seo_abs_url(get_permalink($ancestor_id)),
				];
			}
			$items[] = ['label' => get_the_title(), 'url' => ''];
			return $items;
		}

		return $items;
	}
}

if (!function_exists('italika_seo_breadcrumb_schema')) {
	function italika_seo_breadcrumb_schema() {
		$items = italika_seo_breadcrumb_items();

		if (count($items) < 2) {
			return null;
		}

		$list = [];

		foreach ($items as $index => $item) {
			$url = !empty($item['url']) ? $item['url'] : italika_seo_current_url();
			$list[] = [
				'@type' => 'ListItem',
				'position' => $index + 1,
				'name' => $item['label'],
				'item' => $url,
			];
		}

		return [
			'@type' => 'BreadcrumbList',
			'@id' => italika_seo_current_url() . '#breadcrumbs',
			'itemListElement' => $list,
		];
	}
}

if (!function_exists('italika_seo_render_breadcrumbs')) {
	function italika_seo_render_breadcrumbs() {
		if (!italika_seo_option('enable_breadcrumbs') || is_front_page()) {
			return;
		}

		$items = italika_seo_breadcrumb_items();

		if (count($items) < 2) {
			return;
		}
		?>
		<nav class="seo-breadcrumbs" aria-label="Хлебные крошки">
			<div class="container">
				<ol class="seo-breadcrumbs__list">
					<?php foreach ($items as $index => $item) : ?>
						<li class="seo-breadcrumbs__item">
							<?php if (!empty($item['url']) && $index < count($items) - 1) : ?>
								<a class="seo-breadcrumbs__link" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a>
							<?php else : ?>
								<span class="seo-breadcrumbs__current"><?php echo esc_html($item['label']); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</nav>
		<?php
	}
}

add_action('wp_head', function () {
	?>
	<style>
		.seo-breadcrumbs { padding: 22px 0 0; color: var(--text-soft); font-size: 14px; }
		.seo-breadcrumbs__list { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 0; padding: 0; list-style: none; }
		.seo-breadcrumbs__item { display: inline-flex; align-items: center; gap: 8px; min-width: 0; }
		.seo-breadcrumbs__item:not(:last-child)::after { content: "/"; color: var(--text-muted); }
		.seo-breadcrumbs__link { color: var(--accent-strong); font-weight: 600; }
		.seo-breadcrumbs__current { color: var(--text-soft); overflow-wrap: anywhere; }
		.not-found-page { padding: 76px 0 92px; }
		.not-found-page__grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(280px, 420px); gap: 36px; align-items: center; }
		.not-found-page__eyebrow { margin: 0 0 12px; color: var(--accent-strong); font-weight: 800; text-transform: uppercase; letter-spacing: .08em; }
		.not-found-page__title { margin: 0; color: var(--text); font-size: clamp(38px, 6vw, 76px); line-height: .95; }
		.not-found-page__text { max-width: 620px; margin: 22px 0 0; color: var(--text-soft); font-size: 18px; line-height: 1.6; }
		.not-found-page__actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
		.not-found-page__button { border: 1px solid var(--accent); border-radius: 8px; padding: 14px 18px; color: var(--color-white); background: var(--accent); font-weight: 800; }
		.not-found-page__button--ghost { color: var(--accent-strong); background: transparent; }
		.not-found-page__panel { border: 1px solid var(--color-accent-a12); border-radius: 8px; background: var(--color-white-a78); padding: 24px; }
		.not-found-page__panel-title { margin: 0 0 16px; font-size: 20px; }
		.not-found-page__links { display: grid; gap: 12px; margin: 0; padding: 0; list-style: none; }
		.not-found-page__links a { color: var(--accent-strong); font-weight: 700; }
		@media (max-width: 760px) {
			.not-found-page__grid { grid-template-columns: 1fr; }
			.not-found-page { padding: 48px 0 68px; }
		}
	</style>
	<?php
}, 30);

if (!function_exists('italika_seo_sitemap_files')) {
	function italika_seo_sitemap_files() {
		return [
			'sitemap.xml' => 'index',
			'sitemap-pages.xml' => 'pages',
			'sitemap-products.xml' => 'products',
			'sitemap-product-categories.xml' => 'product-categories',
			'sitemap-news.xml' => 'news',
			'sitemap-recipes.xml' => 'recipes',
		];
	}
}

if (!function_exists('italika_seo_xml_escape')) {
	function italika_seo_xml_escape($value) {
		return esc_xml((string) $value);
	}
}

if (!function_exists('italika_seo_sitemap_url_entry')) {
	function italika_seo_sitemap_url_entry($url, $lastmod = '', $priority = '') {
		$xml = "\t<url>\n";
		$xml .= "\t\t<loc>" . italika_seo_xml_escape(italika_seo_abs_url($url)) . "</loc>\n";

		if ($lastmod !== '') {
			$xml .= "\t\t<lastmod>" . italika_seo_xml_escape(gmdate('c', strtotime($lastmod))) . "</lastmod>\n";
		}

		if ($priority !== '') {
			$xml .= "\t\t<priority>" . italika_seo_xml_escape($priority) . "</priority>\n";
		}

		$xml .= "\t</url>\n";

		return $xml;
	}
}

if (!function_exists('italika_seo_sitemap_post_entries')) {
	function italika_seo_sitemap_post_entries($post_type, $priority = '0.7') {
		$entries = '';
		$query = new WP_Query([
			'post_type' => $post_type,
			'post_status' => 'publish',
			'posts_per_page' => 5000,
			'fields' => 'ids',
			'orderby' => 'modified',
			'order' => 'DESC',
			'no_found_rows' => true,
			'ignore_sticky_posts' => true,
		]);

		foreach ($query->posts as $post_id) {
			if (get_post_meta($post_id, '_italika_seo_noindex', true) === '1') {
				continue;
			}

			$entries .= italika_seo_sitemap_url_entry(get_permalink($post_id), get_post_modified_time('c', true, $post_id), $priority);
		}

		wp_reset_postdata();

		return $entries;
	}
}

if (!function_exists('italika_seo_sitemap_pages_xml')) {
	function italika_seo_sitemap_pages_xml() {
		$entries = '';
		$entries .= italika_seo_sitemap_url_entry(home_url('/'), get_lastpostmodified('GMT'), '1.0');

		$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/');
		if ($shop_url) {
			$entries .= italika_seo_sitemap_url_entry($shop_url, get_lastpostmodified('GMT'), '0.9');
		}

		$entries .= italika_seo_sitemap_url_entry(home_url('/news/'), get_lastpostmodified('GMT'), '0.7');
		$entries .= italika_seo_sitemap_url_entry(home_url('/recipes/'), get_lastpostmodified('GMT'), '0.7');
		$entries .= italika_seo_sitemap_post_entries('page', '0.6');

		return $entries;
	}
}

if (!function_exists('italika_seo_sitemap_products_xml')) {
	function italika_seo_sitemap_products_xml() {
		return post_type_exists('product') ? italika_seo_sitemap_post_entries('product', '0.8') : '';
	}
}

if (!function_exists('italika_seo_sitemap_product_categories_xml')) {
	function italika_seo_sitemap_product_categories_xml() {
		if (!taxonomy_exists('product_cat')) {
			return '';
		}

		$entries = '';
		$terms = get_terms([
			'taxonomy' => 'product_cat',
			'hide_empty' => true,
			'orderby' => 'name',
			'order' => 'ASC',
		]);

		if (is_wp_error($terms) || !is_array($terms)) {
			return '';
		}

		foreach ($terms as $term) {
			if (get_term_meta($term->term_id, '_italika_seo_noindex', true) === '1') {
				continue;
			}

			$url = function_exists('italika_catalog_get_url') ? italika_catalog_get_url($term->slug) : get_term_link($term);

			if (!is_wp_error($url)) {
				$entries .= italika_seo_sitemap_url_entry($url, '', '0.7');
			}
		}

		$sale_url = function_exists('italika_catalog_get_url') ? italika_catalog_get_url('sale') : add_query_arg('category', 'sale', function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/'));
		$entries .= italika_seo_sitemap_url_entry($sale_url, '', '0.6');

		return $entries;
	}
}

if (!function_exists('italika_seo_sitemap_news_xml')) {
	function italika_seo_sitemap_news_xml() {
		return italika_seo_sitemap_post_entries('post', '0.6');
	}
}

if (!function_exists('italika_seo_sitemap_recipes_xml')) {
	function italika_seo_sitemap_recipes_xml() {
		return post_type_exists('recipe') ? italika_seo_sitemap_post_entries('recipe', '0.6') : '';
	}
}

if (!function_exists('italika_seo_sitemap_index_xml')) {
	function italika_seo_sitemap_index_xml() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach (italika_seo_sitemap_files() as $file => $type) {
			if ($type === 'index') {
				continue;
			}

			$xml .= "\t<sitemap>\n";
			$xml .= "\t\t<loc>" . italika_seo_xml_escape(italika_seo_abs_url(home_url('/' . $file))) . "</loc>\n";
			$xml .= "\t\t<lastmod>" . italika_seo_xml_escape(gmdate('c')) . "</lastmod>\n";
			$xml .= "\t</sitemap>\n";
		}

		$xml .= '</sitemapindex>';

		return $xml;
	}
}

if (!function_exists('italika_seo_sitemap_urlset_xml')) {
	function italika_seo_sitemap_urlset_xml($type) {
		$map = [
			'pages' => 'italika_seo_sitemap_pages_xml',
			'products' => 'italika_seo_sitemap_products_xml',
			'product-categories' => 'italika_seo_sitemap_product_categories_xml',
			'news' => 'italika_seo_sitemap_news_xml',
			'recipes' => 'italika_seo_sitemap_recipes_xml',
		];

		$entries = isset($map[$type]) && function_exists($map[$type]) ? call_user_func($map[$type]) : '';
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		$xml .= $entries;
		$xml .= '</urlset>';

		return $xml;
	}
}

if (!function_exists('italika_seo_sitemap_xml')) {
	function italika_seo_sitemap_xml($type) {
		$type = sanitize_key($type);
		$cache_key = 'italika_seo_sitemap_' . $type . '_' . md5(italika_seo_abs_url(home_url('/')));
		$cached = get_transient($cache_key);

		if (is_string($cached) && $cached !== '') {
			return $cached;
		}

		$xml = $type === 'index' ? italika_seo_sitemap_index_xml() : italika_seo_sitemap_urlset_xml($type);

		set_transient($cache_key, $xml, 12 * HOUR_IN_SECONDS);

		return $xml;
	}
}

if (!function_exists('italika_seo_sitemap_request_type')) {
	function italika_seo_sitemap_request_type() {
		$path = isset($_SERVER['REQUEST_URI']) ? wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) : '';
		$file = basename((string) $path);
		$files = italika_seo_sitemap_files();

		return $files[$file] ?? '';
	}
}

add_action('template_redirect', function () {
	$type = italika_seo_sitemap_request_type();

	if ($type === '') {
		return;
	}

	if (!italika_seo_option('enable_sitemap')) {
		status_header(404);
		exit;
	}

	status_header(200);
	header('Content-Type: application/xml; charset=UTF-8');
	echo italika_seo_sitemap_xml($type);
	exit;
}, 0);

if (!function_exists('italika_seo_flush_sitemap_cache')) {
	function italika_seo_flush_sitemap_cache() {
		global $wpdb;

		$like = $wpdb->esc_like('_transient_italika_seo_sitemap_') . '%';
		$timeout_like = $wpdb->esc_like('_transient_timeout_italika_seo_sitemap_') . '%';
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $like, $timeout_like));
	}
}

add_action('save_post', 'italika_seo_flush_sitemap_cache', 30);
add_action('deleted_post', 'italika_seo_flush_sitemap_cache', 30);
add_action('trashed_post', 'italika_seo_flush_sitemap_cache', 30);
add_action('untrashed_post', 'italika_seo_flush_sitemap_cache', 30);
add_action('created_term', 'italika_seo_flush_sitemap_cache', 30);
add_action('edited_term', 'italika_seo_flush_sitemap_cache', 30);
add_action('delete_term', 'italika_seo_flush_sitemap_cache', 30);
add_action('woocommerce_update_product', 'italika_seo_flush_sitemap_cache', 30);
add_action('update_option_permalink_structure', 'italika_seo_flush_sitemap_cache', 30);
add_action('update_option_home', 'italika_seo_flush_sitemap_cache', 30);
add_action('update_option_siteurl', 'italika_seo_flush_sitemap_cache', 30);
add_action('update_option_' . ITALIKA_SEO_OPTION, 'italika_seo_flush_sitemap_cache', 30);

add_filter('robots_txt', function ($output, $public) {
	$lines = [];

	if ('0' === (string) $public) {
		$lines[] = 'User-agent: *';
		$lines[] = 'Disallow: /';
		return implode("\n", $lines);
	}

	$lines[] = 'User-agent: *';
	$lines[] = 'Disallow: /wp-admin/';
	$lines[] = 'Allow: /wp-admin/admin-ajax.php';
	$lines[] = '';
	$lines[] = 'Disallow: /cart/';
	$lines[] = 'Disallow: /checkout/';
	$lines[] = 'Disallow: /my-account/';
	$lines[] = 'Disallow: /search/';
	$lines[] = 'Disallow: /*?s=';
	$lines[] = 'Disallow: /*?add-to-cart=';
	$lines[] = 'Disallow: /*?orderby=';

	$extra = trim((string) italika_seo_option('robots_extra'));
	if ($extra !== '') {
		$lines[] = '';
		$lines[] = $extra;
	}

	if (italika_seo_option('enable_sitemap')) {
		$lines[] = '';
		$lines[] = 'Sitemap: ' . italika_seo_abs_url(home_url('/sitemap.xml'));
	}

	return implode("\n", $lines);
}, 20, 2);

if (!function_exists('italika_seo_term_field_value')) {
	function italika_seo_term_field_value($term, $key) {
		return $term ? (string) get_term_meta($term->term_id, '_italika_seo_' . $key, true) : '';
	}
}

if (!function_exists('italika_seo_render_term_fields')) {
	function italika_seo_render_term_fields($term = null) {
		$is_edit = $term instanceof WP_Term;
		$title = $is_edit ? italika_seo_term_field_value($term, 'title') : '';
		$description = $is_edit ? italika_seo_term_field_value($term, 'description') : '';
		$og_image_id = $is_edit ? italika_seo_term_field_value($term, 'og_image_id') : '';
		$noindex = $is_edit ? italika_seo_term_field_value($term, 'noindex') : '';

		if ($is_edit) :
			?>
			<tr class="form-field">
				<th scope="row" colspan="2"><h2>SEO ядро</h2></th>
			</tr>
			<tr class="form-field">
				<th scope="row"><label for="italika_seo_term_title">SEO title</label></th>
				<td><input type="text" id="italika_seo_term_title" name="italika_seo_term[title]" value="<?php echo esc_attr($title); ?>"></td>
			</tr>
			<tr class="form-field">
				<th scope="row"><label for="italika_seo_term_description">SEO description</label></th>
				<td><textarea id="italika_seo_term_description" name="italika_seo_term[description]" rows="4"><?php echo esc_textarea($description); ?></textarea></td>
			</tr>
			<tr class="form-field">
				<th scope="row"><label for="italika_seo_term_og_image_id">OG image ID</label></th>
				<td><input type="number" id="italika_seo_term_og_image_id" name="italika_seo_term[og_image_id]" value="<?php echo esc_attr($og_image_id); ?>"></td>
			</tr>
			<tr class="form-field">
				<th scope="row">Noindex</th>
				<td><label><input type="checkbox" name="italika_seo_term[noindex]" value="1" <?php checked($noindex, '1'); ?>> Не индексировать эту категорию</label></td>
			</tr>
			<?php
		else :
			?>
			<div class="form-field">
				<h2>SEO ядро</h2>
			</div>
			<div class="form-field">
				<label for="italika_seo_term_title">SEO title</label>
				<input type="text" id="italika_seo_term_title" name="italika_seo_term[title]" value="">
			</div>
			<div class="form-field">
				<label for="italika_seo_term_description">SEO description</label>
				<textarea id="italika_seo_term_description" name="italika_seo_term[description]" rows="4"></textarea>
			</div>
			<div class="form-field">
				<label for="italika_seo_term_og_image_id">OG image ID</label>
				<input type="number" id="italika_seo_term_og_image_id" name="italika_seo_term[og_image_id]" value="">
			</div>
			<div class="form-field">
				<label><input type="checkbox" name="italika_seo_term[noindex]" value="1"> Не индексировать эту категорию</label>
			</div>
			<?php
		endif;
	}
}

add_action('product_cat_add_form_fields', 'italika_seo_render_term_fields');
add_action('product_cat_edit_form_fields', 'italika_seo_render_term_fields');

if (!function_exists('italika_seo_save_term_fields')) {
	function italika_seo_save_term_fields($term_id) {
		$data = isset($_POST['italika_seo_term']) ? wp_unslash((array) $_POST['italika_seo_term']) : [];

		foreach (['title', 'description', 'og_image_id'] as $key) {
			$value = isset($data[$key]) ? trim((string) $data[$key]) : '';

			if ($key === 'og_image_id') {
				$value = $value !== '' ? (string) absint($value) : '';
			} else {
				$value = sanitize_text_field($value);
			}

			if ($value === '') {
				delete_term_meta($term_id, '_italika_seo_' . $key);
			} else {
				update_term_meta($term_id, '_italika_seo_' . $key, $value);
			}
		}

		if (!empty($data['noindex'])) {
			update_term_meta($term_id, '_italika_seo_noindex', '1');
		} else {
			delete_term_meta($term_id, '_italika_seo_noindex');
		}
	}
}

add_action('created_product_cat', 'italika_seo_save_term_fields');
add_action('edited_product_cat', 'italika_seo_save_term_fields');
