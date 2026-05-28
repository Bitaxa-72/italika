<?php

defined('ABSPATH') || exit;

if (!function_exists('italika_news_strip_shortcode')) {
	function italika_news_strip_shortcode() {
		if (function_exists('italika_posts_archive_has_published_posts') && !italika_posts_archive_has_published_posts('news')) {
			return '';
		}

		$q = new WP_Query([
			'post_type' => 'post',
			'post_status' => 'publish',
			'posts_per_page' => 10,
			'ignore_sticky_posts' => true,
			'no_found_rows' => true,
		]);

		ob_start();

		if ($q->have_posts()) :
		?>
<section class="news-strip">
	<div class="container">
		<div class="news-strip__head">
			<div class="news-strip__lead">
				<h2 class="news-strip__title">Новости</h2>

				<button class="news-strip__subscribe" type="button" data-subscribe-open>
					<svg class="news-strip__subscribe-icon" viewBox="0 0 24 24" fill="none">
						<path d="M4.5 6.5h15v11h-15v-11Z" stroke-width="1.8" stroke-linejoin="round"></path>
						<path d="m5 7 7 6 7-6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
					<span>Подписаться</span>
				</button>
			</div>

			<div class="news-strip__actions">
				<a class="news-strip__all" href="<?php echo esc_url(home_url('/news/')); ?>">Все новости</a>

				<div class="news-strip__nav">
					<button class="news-strip__arrow news-strip__arrow--prev js-news-strip-prev" type="button" aria-label="Назад"></button>
					<button class="news-strip__arrow news-strip__arrow--next js-news-strip-next" type="button" aria-label="Вперед"></button>
				</div>
			</div>
		</div>

		<div class="news-strip__slider swiper js-news-strip-slider">
			<div class="swiper-wrapper">

				<?php while ($q->have_posts()) : $q->the_post(); ?>

					<?php
					$url = get_permalink();
					$title = get_the_title();
					$date = get_the_date('d F Y');
					?>

					<div class="swiper-slide news-strip__slide">
						<a class="news-strip__card" href="<?php echo esc_url($url); ?>">

							<div class="news-strip__media">
								<?php if (has_post_thumbnail()) : ?>
									<?php the_post_thumbnail('medium_large', [
										'class' => 'news-strip__image',
										'loading' => 'lazy',
										'decoding' => 'async'
									]); ?>
								<?php endif; ?>
							</div>

							<div class="news-strip__body">
								<h3 class="news-strip__name">
									<?php echo esc_html($title); ?>
								</h3>

								<div class="news-strip__date">
									<?php echo esc_html($date); ?>
								</div>
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

	add_shortcode('italika_news_strip', 'italika_news_strip_shortcode');
}
