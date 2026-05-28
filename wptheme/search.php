<?php
get_header();
?>

<main id="primary" class="site-main">
	<?php
	if (function_exists('italika_search_render_page')) {
		italika_search_render_page();
	}
	?>
</main>

<?php
get_footer();
