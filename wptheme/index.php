<?php
get_header();

if (have_posts()) :
	while (have_posts()) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class('page-content'); ?>>
			<div class="container">
				<h1 class="page-content__title"><?php the_title(); ?></h1>
				<div class="page-content__body">
					<?php the_content(); ?>
				</div>
			</div>
		</article>
		<?php
	endwhile;
endif;

get_footer();
