<?php
defined('ABSPATH') || exit;

if (!defined('ITALIKA_FAVORITES_META_KEY')) {
    define('ITALIKA_FAVORITES_META_KEY', '_italika_favorite_product_ids');
}

if (!function_exists('italika_favorites_get_login_trigger_selector')) {
    function italika_favorites_get_login_trigger_selector()
    {
        return (string) apply_filters('italika_favorites_login_trigger_selector', '');
    }
}

if (!function_exists('italika_favorites_get_user_ids')) {
    function italika_favorites_get_user_ids($user_id = 0)
    {
        $user_id = $user_id ? (int) $user_id : (int) get_current_user_id();

        if ($user_id <= 0) {
            return [];
        }

        $ids = get_user_meta($user_id, ITALIKA_FAVORITES_META_KEY, true);

        if (!is_array($ids)) {
            $ids = [];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($id) {
            return $id > 0;
        })));

        return $ids;
    }
}

if (!function_exists('italika_favorites_save_user_ids')) {
    function italika_favorites_save_user_ids($ids, $user_id = 0)
    {
        $user_id = $user_id ? (int) $user_id : (int) get_current_user_id();

        if ($user_id <= 0) {
            return false;
        }

        $ids = is_array($ids) ? $ids : [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($id) {
            return $id > 0;
        })));

        return update_user_meta($user_id, ITALIKA_FAVORITES_META_KEY, $ids);
    }
}

if (!function_exists('italika_favorites_is_favorite')) {
    function italika_favorites_is_favorite($product_id, $user_id = 0)
    {
        $product_id = (int) $product_id;

        if ($product_id <= 0) {
            return false;
        }

        $ids = italika_favorites_get_user_ids($user_id);

        return in_array($product_id, $ids, true);
    }
}

if (!function_exists('italika_favorites_toggle')) {
    function italika_favorites_toggle($product_id, $user_id = 0)
    {
        $user_id = $user_id ? (int) $user_id : (int) get_current_user_id();
        $product_id = (int) $product_id;

        if ($user_id <= 0 || $product_id <= 0) {
            return [
                'success' => false,
                'active' => false,
                'count' => 0,
                'ids' => [],
            ];
        }

        $product = function_exists('wc_get_product') ? wc_get_product($product_id) : null;

        if (!$product) {
            return [
                'success' => false,
                'active' => false,
                'count' => 0,
                'ids' => [],
            ];
        }

        $ids = italika_favorites_get_user_ids($user_id);
        $index = array_search($product_id, $ids, true);

        if ($index !== false) {
            unset($ids[$index]);
            $active = false;
        } else {
            $ids[] = $product_id;
            $active = true;
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        italika_favorites_save_user_ids($ids, $user_id);

        return [
            'success' => true,
            'active' => $active,
            'count' => count($ids),
            'ids' => $ids,
            'product_id' => $product_id,
            'title' => $product->get_name(),
        ];
    }
}

if (!function_exists('italika_favorites_ajax_toggle')) {
    function italika_favorites_ajax_toggle()
    {
        check_ajax_referer('italika_favorites_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_success([
                'requires_auth' => true,
                'login_trigger_selector' => italika_favorites_get_login_trigger_selector(),
            ]);
        }

        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $result = italika_favorites_toggle($product_id, get_current_user_id());

        if (empty($result['success'])) {
            wp_send_json_error([
                'message' => 'Не удалось обновить избранное',
            ]);
        }

        wp_send_json_success([
            'requires_auth' => false,
            'active' => !empty($result['active']),
            'count' => (int) $result['count'],
            'product_id' => (int) $result['product_id'],
            'label' => !empty($result['active'])
                ? 'Убрать из избранного: ' . $result['title']
                : 'Добавить в избранное: ' . $result['title'],
        ]);
    }
}

add_action('wp_ajax_italika_favorites_toggle', 'italika_favorites_ajax_toggle');
add_action('wp_ajax_nopriv_italika_favorites_toggle', 'italika_favorites_ajax_toggle');

if (!function_exists('italika_favorites_enqueue_assets')) {
    function italika_favorites_enqueue_assets()
    {
        $handle = 'italika-favorites';
        $path = get_template_directory() . '/modules/favorites/assets/favorites.js';
        $src = get_template_directory_uri() . '/modules/favorites/assets/favorites.js';
        $page_handle = 'italika-favorites-page';
        $page_path = get_template_directory() . '/modules/favorites/assets/favorites-page.js';
        $page_src = get_template_directory_uri() . '/modules/favorites/assets/favorites-page.js';

        if (!file_exists($path)) {
            return;
        }

        wp_enqueue_script($handle, $src, [], filemtime($path), true);

        wp_localize_script($handle, 'italikaFavoritesData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('italika_favorites_nonce'),
            'loginTriggerSelector' => italika_favorites_get_login_trigger_selector(),
        ]);

        if (file_exists($page_path)) {
            wp_enqueue_script($page_handle, $page_src, [$handle], filemtime($page_path), true);
            wp_localize_script($page_handle, 'italikaFavoritesPageData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('italika_favorites_page_nonce'),
            ]);
        }
    }
}

if (!function_exists('italika_favorites_get_count')) {
    function italika_favorites_get_count($user_id = 0)
    {
        return count(italika_favorites_get_user_ids($user_id));
    }
}

if (!function_exists('italika_favorites_get_archive_url')) {
    function italika_favorites_get_archive_url()
    {
        return home_url('/favorites/');
    }
}

add_action('wp_enqueue_scripts', 'italika_favorites_enqueue_assets');

if (!function_exists('italika_favorites_is_archive_path')) {
    function italika_favorites_is_archive_path()
    {
        global $wp;

        $request = isset($wp->request) ? trim((string) $wp->request, '/') : '';

        if ($request === 'favorites') {
            return true;
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            $path = trim((string) wp_parse_url(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])), PHP_URL_PATH), '/');

            return $path === 'favorites';
        }

        return false;
    }
}

if (!function_exists('italika_favorites_register_route')) {
    function italika_favorites_register_route()
    {
        add_rewrite_rule('^favorites/?$', 'index.php?italika_favorites=1', 'top');

        if (get_option('italika_favorites_route_flushed') !== 'yes') {
            flush_rewrite_rules(false);
            update_option('italika_favorites_route_flushed', 'yes', false);
        }
    }
    add_action('init', 'italika_favorites_register_route', 20);
}

add_filter('query_vars', function ($vars) {
    $vars[] = 'italika_favorites';

    return $vars;
});

add_action('parse_request', function ($wp) {
    if (isset($wp->request) && trim((string) $wp->request, '/') === 'favorites') {
        $wp->query_vars['italika_favorites'] = '1';
    }
});

add_filter('pre_handle_404', function ($preempt, $wp_query) {
    if (!italika_favorites_is_archive_request()) {
        return $preempt;
    }

    $wp_query->is_404 = false;
    status_header(200);

    return true;
}, 10, 2);

if (!function_exists('italika_favorites_get_catalog_url')) {
    function italika_favorites_get_catalog_url()
    {
        if (function_exists('wc_get_page_permalink')) {
            $url = wc_get_page_permalink('shop');

            if ($url) {
                return $url;
            }
        }

        return home_url('/catalog/');
    }
}

if (!function_exists('italika_favorites_is_archive_request')) {
    function italika_favorites_is_archive_request()
    {
        if ((string) get_query_var('italika_favorites') === '1') {
            return true;
        }

        if (is_page('favorites')) {
            return true;
        }

        return italika_favorites_is_archive_path();
    }
}

if (!function_exists('italika_favorites_template_include')) {
    function italika_favorites_template_include($template)
    {
        if (!italika_favorites_is_archive_request()) {
            return $template;
        }

        $favorites_template = get_template_directory() . '/modules/favorites/favorites-page.php';

        if (!file_exists($favorites_template)) {
            return $template;
        }

        status_header(200);
        nocache_headers();

        return $favorites_template;
    }
    add_filter('template_include', 'italika_favorites_template_include');
}

if (!function_exists('italika_favorites_get_product_ids')) {
    function italika_favorites_get_product_ids($category_id = 0, $user_id = 0)
    {
        $ids = italika_favorites_get_user_ids($user_id);
        $category_id = (int) $category_id;

        if (empty($ids)) {
            return [];
        }

        $query_args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'post__in' => $ids,
            'orderby' => 'post__in',
            'fields' => 'ids',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
        ];

        if ($category_id > 0) {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => [$category_id],
                ],
            ];
        }

        $query = new WP_Query($query_args);

        return $query->have_posts() ? array_map('intval', $query->posts) : [];
    }
}

if (!function_exists('italika_favorites_render_cards')) {
    function italika_favorites_render_cards($product_ids, $offset = 0, $limit = 12)
    {
        if (empty($product_ids)) {
            return '';
        }

        $items = array_slice(array_values(array_map('intval', $product_ids)), max(0, (int) $offset), max(1, (int) $limit));
        $html = '';

        foreach ($items as $product_id) {
            if (function_exists('italika_ecomcard_render')) {
                $html .= italika_ecomcard_render($product_id, ['card_class' => 'favorites-page__card']);
            }
        }

        return $html;
    }
}

if (!function_exists('italika_favorites_get_category_items')) {
    function italika_favorites_get_category_items($product_ids)
    {
        $product_ids = array_values(array_filter(array_map('intval', (array) $product_ids)));

        if (empty($product_ids)) {
            return [];
        }

        $counts = [];
        $names = [];

        foreach ($product_ids as $product_id) {
            $terms = wp_get_post_terms($product_id, 'product_cat');

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                $term_id = (int) $term->term_id;
                $counts[$term_id] = isset($counts[$term_id]) ? $counts[$term_id] + 1 : 1;
                $names[$term_id] = $term->name;
            }
        }

        if (empty($counts)) {
            return [];
        }

        $items = [];

        foreach ($counts as $term_id => $count) {
            $items[] = [
                'term_id' => (int) $term_id,
                'name' => $names[$term_id],
                'count' => (int) $count,
            ];
        }

        usort($items, static function ($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });

        return $items;
    }
}

if (!function_exists('italika_favorites_render_filters')) {
    function italika_favorites_render_filters($product_ids, $active_category_id = 0)
    {
        $product_ids = array_values(array_filter(array_map('intval', (array) $product_ids)));
        $active_category_id = (int) $active_category_id;
        $items = italika_favorites_get_category_items($product_ids);

        if (empty($product_ids)) {
            return '';
        }

        ob_start();
        ?>
        <button class="favorites-page__filter<?php echo $active_category_id <= 0 ? ' is-active' : ''; ?>" type="button" data-category-id="0" aria-pressed="<?php echo $active_category_id <= 0 ? 'true' : 'false'; ?>">
            <span>Все</span>
            <span class="favorites-page__filter-count"><?php echo (int) count($product_ids); ?></span>
        </button>

        <?php foreach ($items as $item) : ?>
            <button class="favorites-page__filter<?php echo $active_category_id === (int) $item['term_id'] ? ' is-active' : ''; ?>" type="button" data-category-id="<?php echo esc_attr((string) $item['term_id']); ?>" aria-pressed="<?php echo $active_category_id === (int) $item['term_id'] ? 'true' : 'false'; ?>">
                <span><?php echo esc_html($item['name']); ?></span>
                <span class="favorites-page__filter-count"><?php echo (int) $item['count']; ?></span>
            </button>
        <?php endforeach; ?>
        <?php
        return trim(ob_get_clean());
    }
}

if (!function_exists('italika_favorites_get_summary')) {
    function italika_favorites_get_summary($total_count, $filtered_count = null, $active_category_id = 0)
    {
        $total_count = (int) $total_count;
        $filtered_count = $filtered_count === null ? $total_count : (int) $filtered_count;

        if ($total_count <= 0) {
            return 'Список пуст. Сохраняйте товары, чтобы быстро вернуться к ним позже.';
        }

        if ((int) $active_category_id > 0) {
            return sprintf('В выбранной категории %d товаров из %d сохраненных.', $filtered_count, $total_count);
        }

        return sprintf('В избранном %d товаров.', $total_count);
    }
}

if (!function_exists('italika_favorites_ajax_page_load')) {
    function italika_favorites_ajax_page_load()
    {
        check_ajax_referer('italika_favorites_page_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_success([
                'requiresAuth' => true,
                'loginUrl' => function_exists('italika_wc_get_account_url') ? italika_wc_get_account_url() : wp_login_url(italika_favorites_get_archive_url()),
            ]);
        }

        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        $offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
        $limit = isset($_POST['limit']) ? max(1, (int) $_POST['limit']) : 12;

        $all_ids = italika_favorites_get_product_ids(0);
        $filtered_ids = $category_id > 0 ? italika_favorites_get_product_ids($category_id) : $all_ids;
        $html = italika_favorites_render_cards($filtered_ids, $offset, $limit);
        $loaded_count = min($limit, max(0, count($filtered_ids) - $offset));
        $next_offset = $offset + $loaded_count;

        wp_send_json_success([
            'html' => $html,
            'filtersHtml' => italika_favorites_render_filters($all_ids, $category_id),
            'summary' => italika_favorites_get_summary(count($all_ids), count($filtered_ids), $category_id),
            'count' => $loaded_count,
            'totalCount' => count($all_ids),
            'filteredCount' => count($filtered_ids),
            'nextOffset' => $next_offset,
            'hasMore' => $next_offset < count($filtered_ids),
            'isEmpty' => count($all_ids) === 0,
        ]);
    }
}

add_action('wp_ajax_italika_favorites_page_load', 'italika_favorites_ajax_page_load');
add_action('wp_ajax_nopriv_italika_favorites_page_load', 'italika_favorites_ajax_page_load');
