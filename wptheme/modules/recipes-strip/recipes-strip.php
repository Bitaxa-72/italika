<?php

defined('ABSPATH') || exit;

if (!function_exists('italika_recipes_strip_shortcode')) {
	function italika_recipes_strip_shortcode() {
		if (function_exists('italika_posts_archive_has_published_posts') && !italika_posts_archive_has_published_posts('recipes')) {
			return '';
		}

		$q = new WP_Query([
			'post_type' => 'recipe',
			'post_status' => 'publish',
			'posts_per_page' => 10,
			'ignore_sticky_posts' => true,
			'no_found_rows' => true,
		]);

		ob_start();

		if ($q->have_posts()) :
		?>
<section class="recipe-cats">
	<div class="container">
		<div class="recipe-cats__head">
			<div class="recipe-cats__lead">
				<h2 class="recipe-cats__title">Рецепты</h2>

				<button class="recipe-cats__subscribe" type="button" data-subscribe-open>
					<svg class="recipe-cats__subscribe-icon" viewBox="0 0 24 24" fill="none">
						<path d="M4.5 6.5h15v11h-15v-11Z" stroke-width="1.8" stroke-linejoin="round"></path>
						<path d="m5 7 7 6 7-6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
					<span>Подписаться</span>
				</button>
			</div>

			<div class="recipe-cats__actions">
				<a class="recipe-cats__all" href="<?php echo esc_url(home_url('/recipes/')); ?>">Все рецепты</a>

				<div class="recipe-cats__nav">
					<button class="recipe-cats__arrow recipe-cats__arrow--prev js-recipe-cats-prev" type="button" aria-label="Назад"></button>
					<button class="recipe-cats__arrow recipe-cats__arrow--next js-recipe-cats-next" type="button" aria-label="Вперед"></button>
				</div>
			</div>
		</div>

		<div class="recipe-cats__slider swiper js-recipe-cats-slider">
			<div class="swiper-wrapper">

				<?php while ($q->have_posts()) : $q->the_post(); ?>

					<?php
					$url = get_permalink();
					$title = get_the_title();

					$terms = get_the_terms(get_the_ID(), 'category');
					$category = '';

					if (!empty($terms) && !is_wp_error($terms)) {
						$category = $terms[0]->name;
					}
					?>

					<div class="swiper-slide recipe-cats__slide">
						<a class="recipe-cats__card" href="<?php echo esc_url($url); ?>">

							<div class="recipe-cats__media">
								<?php if (has_post_thumbnail()) : ?>
									<?php the_post_thumbnail('medium_large', [
										'class' => 'recipe-cats__image',
										'loading' => 'lazy',
										'decoding' => 'async'
									]); ?>
								<?php endif; ?>
							</div>

							<div class="recipe-cats__body">
								<div class="recipe-cats__meta">
									<?php echo esc_html($category !== '' ? $category : 'Рецепты'); ?>
								</div>

								<h3 class="recipe-cats__name">
									<?php echo esc_html($title); ?>
								</h3>
							</div>

						</a>
					</div>

				<?php endwhile; wp_reset_postdata(); ?>

			</div>
		</div>
	</div>
</section>
		<?php
		endif;

		return ob_get_clean();
	}

	add_shortcode('italika_recipes_strip', 'italika_recipes_strip_shortcode');
}
