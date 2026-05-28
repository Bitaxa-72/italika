<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_old_import_cleanup_product_ids')) {
	function italika_old_import_cleanup_product_ids($limit = 100) {
		$query = new WP_Query([
			'post_type' => 'product',
			'post_status' => 'any',
			'posts_per_page' => max(1, (int) $limit),
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'no_found_rows' => true,
			'meta_query' => [
				'relation' => 'OR',
				[
					'key' => 'old_website_id',
					'compare' => 'EXISTS',
				],
				[
					'key' => '_old_website_id',
					'compare' => 'EXISTS',
				],
			],
		]);

		return array_map('intval', $query->posts);
	}
}

if (!function_exists('italika_old_import_cleanup_count')) {
	function italika_old_import_cleanup_count() {
		$query = new WP_Query([
			'post_type' => 'product',
			'post_status' => 'any',
			'posts_per_page' => 1,
			'fields' => 'ids',
			'meta_query' => [
				'relation' => 'OR',
				[
					'key' => 'old_website_id',
					'compare' => 'EXISTS',
				],
				[
					'key' => '_old_website_id',
					'compare' => 'EXISTS',
				],
			],
		]);

		return (int) $query->found_posts;
	}
}

add_action('admin_menu', function () {
	if (!class_exists('WooCommerce')) {
		return;
	}

	add_submenu_page(
		'woocommerce',
		'Очистка старого импорта',
		'Очистка старого импорта',
		'manage_woocommerce',
		'italika-old-import-cleanup',
		'italika_old_import_cleanup_page'
	);
});

if (!function_exists('italika_old_import_cleanup_page')) {
	function italika_old_import_cleanup_page() {
		if (!current_user_can('manage_woocommerce')) {
			wp_die(esc_html__('Недостаточно прав.', 'italika'));
		}

		$remaining = italika_old_import_cleanup_count();
		$deleted = isset($_GET['deleted']) ? max(0, (int) $_GET['deleted']) : 0;
		$total_deleted = isset($_GET['total_deleted']) ? max(0, (int) $_GET['total_deleted']) : 0;
		$run = isset($_GET['run']) && $_GET['run'] === '1';
		?>
		<div class="wrap">
			<h1>Очистка старого импорта</h1>
			<p>Удаляются только товары WooCommerce с меткой <code>old_website_id</code>. Обычные товары без этой метки не трогаются.</p>

			<?php if ($deleted > 0) : ?>
				<div class="notice notice-success"><p>Удалено за прошлую пачку: <?php echo esc_html((string) $deleted); ?>.</p></div>
			<?php endif; ?>

			<table class="widefat striped" style="max-width: 760px;">
				<tbody>
					<tr>
						<th scope="row">Осталось товаров старого импорта</th>
						<td><?php echo esc_html((string) $remaining); ?></td>
					</tr>
					<tr>
						<th scope="row">Удалено за текущий запуск</th>
						<td><?php echo esc_html((string) $total_deleted); ?></td>
					</tr>
					<tr>
						<th scope="row">Размер пачки</th>
						<td>100 товаров</td>
					</tr>
				</tbody>
			</table>

			<?php if ($remaining > 0) : ?>
				<form id="italika-old-import-cleanup-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 18px;">
					<input type="hidden" name="action" value="italika_old_import_delete_batch">
					<input type="hidden" name="run" value="<?php echo esc_attr($run ? '1' : '0'); ?>">
					<input type="hidden" name="total_deleted" value="<?php echo esc_attr((string) $total_deleted); ?>">
					<?php wp_nonce_field('italika_old_import_delete_batch'); ?>
					<button type="submit" class="button button-primary">Удалить следующую пачку</button>
					<a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'italika-old-import-cleanup', 'run' => '1'], admin_url('admin.php'))); ?>">Запустить автоочистку</a>
				</form>
				<?php if ($run) : ?>
					<p>Автоочистка идет. Следующая пачка запустится автоматически.</p>
					<script>
						window.setTimeout(function () {
							document.getElementById('italika-old-import-cleanup-form').submit();
						}, 400);
					</script>
				<?php endif; ?>
			<?php else : ?>
				<div class="notice notice-success" style="margin-top: 18px;"><p>Товаров старого импорта не осталось.</p></div>
			<?php endif; ?>
		</div>
		<?php
	}
}

add_action('admin_post_italika_old_import_delete_batch', function () {
	if (!current_user_can('manage_woocommerce')) {
		wp_die(esc_html__('Недостаточно прав.', 'italika'));
	}

	check_admin_referer('italika_old_import_delete_batch');

	$run = isset($_POST['run']) && $_POST['run'] === '1';
	$total_deleted = isset($_POST['total_deleted']) ? max(0, (int) $_POST['total_deleted']) : 0;
	$ids = italika_old_import_cleanup_product_ids(100);
	$deleted = 0;

	foreach ($ids as $product_id) {
		if (wp_delete_post($product_id, true)) {
			$deleted++;
		}
	}

	$total_deleted += $deleted;

	wp_safe_redirect(add_query_arg([
		'page' => 'italika-old-import-cleanup',
		'deleted' => $deleted,
		'total_deleted' => $total_deleted,
		'run' => $run ? '1' : '0',
	], admin_url('admin.php')));
	exit;
});
