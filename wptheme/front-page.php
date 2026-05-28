<?php
get_header();

$assets_uri = get_theme_file_uri('/assets/static');
?>

<?php
$hero_slides = function_exists('get_field') ? get_field('home_hero_slides', 'option') : [];
?>

<?php if (!empty($hero_slides) && is_array($hero_slides)) : ?>
<section class="hero">
	<div class="container">
		<div class="hero__slider swiper js-hero-slider">
			<div class="swiper-wrapper">
				<?php foreach ($hero_slides as $slide) : ?>
					<?php
					$image = isset($slide['image']) ? $slide['image'] : null;
					$image_url = '';
					$image_alt = '';

					if (is_array($image)) {
						$image_url = !empty($image['url']) ? $image['url'] : '';
						$image_alt = !empty($image['alt']) ? $image['alt'] : '';
					} elseif (is_numeric($image)) {
						$image_url = wp_get_attachment_image_url((int) $image, 'full');
						$image_alt = get_post_meta((int) $image, '_wp_attachment_image_alt', true);
					} elseif (is_string($image)) {
						$image_url = $image;
					}

					$title = isset($slide['title']) ? trim((string) $slide['title']) : '';
					$text = isset($slide['text']) ? trim((string) $slide['text']) : '';

					$button_primary_text = isset($slide['button_primary_text']) ? trim((string) $slide['button_primary_text']) : '';
					$button_primary_url = isset($slide['button_primary_url']) ? trim((string) $slide['button_primary_url']) : '';

					$button_secondary_text = isset($slide['button_secondary_text']) ? trim((string) $slide['button_secondary_text']) : '';
					$button_secondary_url = isset($slide['button_secondary_url']) ? trim((string) $slide['button_secondary_url']) : '';

					$has_primary_button = $button_primary_text !== '' && $button_primary_url !== '';
					$has_secondary_button = $button_secondary_text !== '' && $button_secondary_url !== '';

					if ($image_url === '' || $title === '') {
						continue;
					}
					?>
					<div class="swiper-slide hero__slide">
						<div class="hero__media">
							<img class="hero__image" src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt !== '' ? $image_alt : $title); ?>" loading="lazy" decoding="async">
							<div class="hero__overlay"></div>
						</div>

						<div class="hero__content">
							<div class="hero__box">
								<h2 class="hero__title">
									<?php echo esc_html($title); ?>
								</h2>

								<?php if ($text !== '') : ?>
									<p class="hero__text">
										<?php echo esc_html($text); ?>
									</p>
								<?php endif; ?>

								<?php if ($has_primary_button || $has_secondary_button) : ?>
									<div class="hero__actions">
										<?php if ($has_primary_button) : ?>
											<a class="hero__button hero__button--primary" href="<?php echo esc_url($button_primary_url); ?>">
												<?php echo esc_html($button_primary_text); ?>
											</a>
										<?php endif; ?>

										<?php if ($has_secondary_button) : ?>
											<a class="hero__button hero__button--secondary" href="<?php echo esc_url($button_secondary_url); ?>">
												<?php echo esc_html($button_secondary_text); ?>
											</a>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="hero__pagination"></div>

			<div class="hero__nav">
				<button class="hero__arrow hero__arrow--prev" type="button" aria-label="Назад"></button>
				<button class="hero__arrow hero__arrow--next" type="button" aria-label="Вперед"></button>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>


<?php
$catalog_promo_ids = function_exists('italika_get_home_categories_selected_ids') ? italika_get_home_categories_selected_ids() : [];
$catalog_promo_terms = [];

if (!empty($catalog_promo_ids)) {
	$catalog_promo_terms = get_terms([
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'include'    => $catalog_promo_ids,
	]);

	if (!is_wp_error($catalog_promo_terms) && is_array($catalog_promo_terms)) {
		$terms_map = [];

		foreach ($catalog_promo_terms as $term) {
			$terms_map[$term->term_id] = $term;
		}

		$ordered_terms = [];

		foreach ($catalog_promo_ids as $term_id) {
			if (isset($terms_map[$term_id])) {
				$ordered_terms[] = $terms_map[$term_id];
			}
		}

		$catalog_promo_terms = $ordered_terms;
	} else {
		$catalog_promo_terms = [];
	}
}
?>

<?php if (!empty($catalog_promo_terms)) : ?>
<section class="catalog-promo">
	<div class="container">
		<div class="catalog-promo__head">
			<div class="catalog-promo__intro">
				<h2 class="catalog-promo__title">
					Основные категории каталога
				</h2>
			</div>

			<a class="catalog-promo__button" href="/catalog/">Перейти в каталог</a>
		</div>

		<div class="catalog-promo__grid">
			<?php foreach ($catalog_promo_terms as $term) : ?>
				<?php
				$term_link = function_exists('italika_catalog_get_url') ? italika_catalog_get_url($term->slug) : get_term_link($term);

				if (is_wp_error($term_link)) {
					continue;
				}

				$term_name = trim((string) $term->name);
				$term_description = trim(wp_strip_all_tags((string) term_description($term->term_id, 'product_cat')));
				$thumbnail_id = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
				$image_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : '';
				$card_style = $image_url !== '' ? '--card-image: url(\'' . esc_url($image_url) . '\')' : '';
				?>
				<a class="catalog-promo__card" href="<?php echo esc_url($term_link); ?>">
					<span class="catalog-promo__media"<?php echo $card_style !== '' ? ' style="' . esc_attr($card_style) . '"' : ''; ?>></span>
					<span class="catalog-promo__content">
						<span class="catalog-promo__body">
							<?php if ($term_name !== '') : ?>
								<span class="catalog-promo__name"><?php echo esc_html($term_name); ?></span>
							<?php endif; ?>

							<?php if ($term_description !== '') : ?>
								<span class="catalog-promo__meta"><?php echo esc_html($term_description); ?></span>
							<?php endif; ?>
						</span>

						<span class="catalog-promo__bottom">
							<span class="catalog-promo__link">Открыть</span>
							<span class="catalog-promo__arrow" aria-hidden="true"></span>
						</span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>


<?php
echo italika_sale_products_section_render([
	'title' => 'Наши акции',
	'archive_url' => function_exists('italika_catalog_get_url') ? italika_catalog_get_url('sale') : home_url('/catalog/?category=sale'),
	'initial_count' => 12,
	'chunk_size' => 12,
], false);
?>

<?php echo do_shortcode('[italika_news_strip]'); ?>

<?php echo do_shortcode('[italika_recipes_strip]'); ?>

<?php echo do_shortcode('[italika_about_promo]'); ?>

<?php
get_footer();
