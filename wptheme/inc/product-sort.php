<?php
defined('ABSPATH') || exit;

if (!defined('ITALIKA_PRODUCT_SORT_META_KEY')) {
	define('ITALIKA_PRODUCT_SORT_META_KEY', '_italika_product_sort_key');
}

if (!function_exists('italika_product_sort_strtolower')) {
	function italika_product_sort_strtolower($text)
	{
		$text = (string) $text;

		return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
	}
}

if (!function_exists('italika_product_sort_normalize_text')) {
	function italika_product_sort_normalize_text($text)
	{
		$text = html_entity_decode(wp_strip_all_tags((string) $text), ENT_QUOTES, 'UTF-8');
		$text = str_replace(["\xC2\xA0", 'ё', 'Ё', '×', 'х', 'Х'], [' ', 'е', 'е', 'x', 'x', 'x'], $text);
		$text = italika_product_sort_strtolower($text);
		$text = preg_replace('/[«»"\'`]+/u', ' ', $text);
		$text = preg_replace('/\s+/u', ' ', $text);

		return trim((string) $text);
	}
}

if (!function_exists('italika_product_sort_family_key')) {
	function italika_product_sort_family_key($title)
	{
		$text = italika_product_sort_normalize_text($title);
		$units = 'шт|штуки|штук|уп|упак|упаковка|г|гр|кг|мг|л|мл|см|мм|м|pcs?|pc|kg|g|gr|ml|l';

		$text = preg_replace('/(?<![\p{L}\p{N}])\d+(?:[,.]\d+)?\s*[x*]\s*\d+(?:[,.]\d+)?\s*(?:' . $units . ')(?![\p{L}\p{N}])/u', ' ', $text);
		$text = preg_replace('/(?<![\p{L}\p{N}])\d+(?:[,.]\d+)?\s*(?:' . $units . ')(?![\p{L}\p{N}])/u', ' ', $text);
		$text = preg_replace('/(?<![\p{L}\p{N}])(?:уп|упак|упаковка|фасовка|фас)(?![\p{L}\p{N}])/u', ' ', $text);
		$text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
		$text = preg_replace('/\s+/u', ' ', $text);

		return trim((string) $text);
	}
}

if (!function_exists('italika_product_sort_unit_value')) {
	function italika_product_sort_unit_value($value, $unit)
	{
		$value = (float) str_replace(',', '.', (string) $value);
		$unit = italika_product_sort_strtolower((string) $unit);

		if (in_array($unit, ['кг', 'kg'], true)) {
			return ['weight', $value * 1000];
		}

		if (in_array($unit, ['мг'], true)) {
			return ['weight', $value / 1000];
		}

		if (in_array($unit, ['г', 'гр', 'g', 'gr'], true)) {
			return ['weight', $value];
		}

		if (in_array($unit, ['л', 'l'], true)) {
			return ['volume', $value * 1000];
		}

		if (in_array($unit, ['мл', 'ml'], true)) {
			return ['volume', $value];
		}

		if (in_array($unit, ['м', 'm'], true)) {
			return ['length', $value * 1000];
		}

		if (in_array($unit, ['см'], true)) {
			return ['length', $value * 10];
		}

		if (in_array($unit, ['мм'], true)) {
			return ['length', $value];
		}

		return ['count', $value];
	}
}

if (!function_exists('italika_product_sort_variant_key')) {
	function italika_product_sort_variant_key($title)
	{
		$text = italika_product_sort_normalize_text($title);
		$tokens = [];

		if (preg_match_all('/(?<![\p{L}\p{N}])(\d+(?:[,.]\d+)?)\s*(шт|штуки|штук|уп|упак|упаковка|г|гр|кг|мг|л|мл|см|мм|м|pcs?|pc|kg|g|gr|ml|l)(?![\p{L}\p{N}])/u', $text, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				[$group, $value] = italika_product_sort_unit_value($match[1], $match[2]);
				$tokens[] = $group . ':' . sprintf('%012d', (int) round($value * 1000));
			}
		}

		return $tokens ? implode(':', $tokens) : 'zzzzzz';
	}
}

if (!function_exists('italika_product_sort_key')) {
	function italika_product_sort_key($title)
	{
		$title = (string) $title;
		$family_key = italika_product_sort_family_key($title);
		$variant_key = italika_product_sort_variant_key($title);
		$title_key = preg_replace('/[^\p{L}\p{N}]+/u', ' ', italika_product_sort_normalize_text($title));
		$title_key = preg_replace('/\s+/u', ' ', (string) $title_key);

		return trim($family_key . '|' . $variant_key . '|' . trim((string) $title_key), '| ');
	}
}

if (!function_exists('italika_product_sort_update_product')) {
	function italika_product_sort_update_product($post_id)
	{
		$post_id = (int) $post_id;

		if ($post_id <= 0 || get_post_type($post_id) !== 'product') {
			return;
		}

		$title = get_the_title($post_id);
		$sort_key = italika_product_sort_key($title);

		if ($sort_key !== '') {
			update_post_meta($post_id, ITALIKA_PRODUCT_SORT_META_KEY, $sort_key);
		}
	}
}

if (!function_exists('italika_product_sort_save_product')) {
	function italika_product_sort_save_product($post_id, $post, $update)
	{
		if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
			return;
		}

		italika_product_sort_update_product($post_id);
	}

	add_action('save_post_product', 'italika_product_sort_save_product', 20, 3);
}

if (!function_exists('italika_product_sort_backfill_batch')) {
	function italika_product_sort_backfill_batch($limit = 100)
	{
		$query = new WP_Query([
			'post_type' => 'product',
			'post_status' => 'any',
			'posts_per_page' => max(1, (int) $limit),
			'fields' => 'ids',
			'no_found_rows' => true,
			'meta_query' => [
				[
					'key' => ITALIKA_PRODUCT_SORT_META_KEY,
					'compare' => 'NOT EXISTS',
				],
			],
		]);

		if (!$query->have_posts()) {
			return;
		}

		foreach ($query->posts as $product_id) {
			italika_product_sort_update_product((int) $product_id);
		}
	}
}

if (!function_exists('italika_product_sort_maybe_backfill')) {
	function italika_product_sort_maybe_backfill()
	{
		if (wp_doing_ajax() || wp_doing_cron() || get_option('italika_product_sort_backfill_complete') === 'yes') {
			return;
		}

		$lock_key = 'italika_product_sort_backfill_lock';

		if (get_transient($lock_key)) {
			return;
		}

		set_transient($lock_key, '1', MINUTE_IN_SECONDS);
		$query = new WP_Query([
			'post_type' => 'product',
			'post_status' => 'any',
			'posts_per_page' => 1,
			'fields' => 'ids',
			'no_found_rows' => true,
			'meta_query' => [
				[
					'key' => ITALIKA_PRODUCT_SORT_META_KEY,
					'compare' => 'NOT EXISTS',
				],
			],
		]);

		if (!$query->have_posts()) {
			update_option('italika_product_sort_backfill_complete', 'yes', false);
			return;
		}

		italika_product_sort_backfill_batch(500);
	}

	add_action('admin_init', 'italika_product_sort_maybe_backfill', 50);
}

if (!function_exists('italika_product_sort_apply_to_args')) {
	function italika_product_sort_apply_to_args($args)
	{
		$args['italika_product_smart_sort'] = true;
		$args['orderby'] = 'italika_product_smart_sort';
		$args['order'] = 'ASC';

		return $args;
	}
}

if (!function_exists('italika_product_sort_posts_clauses')) {
	function italika_product_sort_posts_clauses($clauses, $query)
	{
		if (!$query->get('italika_product_smart_sort')) {
			return $clauses;
		}

		global $wpdb;

		$post_type = $query->get('post_type');

		if (is_array($post_type)) {
			$is_product_query = in_array('product', $post_type, true);
		} else {
			$is_product_query = $post_type === 'product';
		}

		if (!$is_product_query) {
			return $clauses;
		}

		$alias = 'italika_product_sort_meta';
		$meta_key = esc_sql(ITALIKA_PRODUCT_SORT_META_KEY);

		if (strpos($clauses['join'], $alias) === false) {
			$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS {$alias} ON ({$wpdb->posts}.ID = {$alias}.post_id AND {$alias}.meta_key = '{$meta_key}')";
		}

		$clauses['orderby'] = "COALESCE({$alias}.meta_value, {$wpdb->posts}.post_title) ASC, {$wpdb->posts}.post_title ASC, {$wpdb->posts}.ID ASC";

		return $clauses;
	}

	add_filter('posts_clauses', 'italika_product_sort_posts_clauses', 20, 2);
}

if (!function_exists('italika_product_sort_main_query')) {
	function italika_product_sort_main_query($query)
	{
		if (is_admin() || !$query->is_main_query()) {
			return;
		}

		$is_product_listing = (function_exists('is_shop') && is_shop())
			|| (function_exists('is_product_category') && is_product_category())
			|| (function_exists('is_product_tag') && is_product_tag())
			|| ($query->is_search() && $query->get('post_type') === 'product');

		if (!$is_product_listing) {
			return;
		}

		if (isset($_GET['orderby']) && sanitize_key(wp_unslash($_GET['orderby'])) !== '') {
			return;
		}

		$post_type = $query->get('post_type');

		if ($post_type === '' || $post_type === null) {
			$query->set('post_type', 'product');
		}

		$query->set('italika_product_smart_sort', true);
		$query->set('orderby', 'italika_product_smart_sort');
		$query->set('order', 'ASC');
	}

	add_action('pre_get_posts', 'italika_product_sort_main_query', 30);
}
