<?php
defined('ABSPATH') || exit;

get_header();
?>

<?php if (function_exists('is_product') && is_product() && function_exists('italika_product_page_render')) : ?>
	<?php italika_product_page_render(); ?>
	<?php
	if (function_exists('italika_sale_products_section_render')) {
		italika_sale_products_section_render([
			'title' => 'Акции недели',
			'archive_url' => function_exists('italika_catalog_get_url') ? italika_catalog_get_url('sale') : '',
			'initial_count' => 4,
			'chunk_size' => 4,
			'section_class' => 'product-page__related',
		]);
	}
	?>
<?php elseif (is_search() && function_exists('italika_search_render_page')) : ?>
	<?php italika_search_render_page(); ?>
<?php elseif (function_exists('is_shop') && is_shop() && function_exists('italika_catalog_render_page')) : ?>
	<?php italika_catalog_render_page(); ?>
	<?php echo do_shortcode('[italika_news_strip]'); ?>
	<?php echo do_shortcode('[italika_recipes_strip]'); ?>
	<?php echo do_shortcode('[italika_about_promo]'); ?>
<?php else : ?>
	<section class="wc-page">
		<div class="container">
			<?php woocommerce_content(); ?>
		</div>
	</section>
<?php endif; ?>

<?php
get_footer();
