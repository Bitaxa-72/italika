<?php
get_header();

if (function_exists('is_cart') && is_cart()) {
	wc_get_template('cart/cart.php');
	get_footer();
	return;
}

if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
	$order_id = absint(get_query_var('order-received'));
	wc_get_template('checkout/thankyou.php', [
		'order' => $order_id ? wc_get_order($order_id) : false,
	]);
	get_footer();
	return;
}

if (function_exists('is_checkout') && is_checkout() && empty($_GET['order-received'])) {
	wc_get_template('checkout/form-checkout.php', [
		'checkout' => WC()->checkout(),
	]);
	get_footer();
	return;
}

if (have_posts()) :
	while (have_posts()) :
		the_post();

		if (function_exists('is_account_page') && is_account_page()) {
			the_content();
		} else {
			?>
			<article id="page-<?php the_ID(); ?>" <?php post_class('page-content'); ?>>
				<div class="container">
					<h1 class="page-content__title"><?php the_title(); ?></h1>
					<div class="page-content__body">
						<?php the_content(); ?>
					</div>
				</div>
			</article>
			<?php
		}
	endwhile;
endif;

get_footer();
