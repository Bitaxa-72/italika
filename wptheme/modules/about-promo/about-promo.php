<?php

defined('ABSPATH') || exit;

if (!function_exists('italika_about_promo_get')) {
	function italika_about_promo_get($key, $default = '') {
		if (!function_exists('get_field')) {
			return $default;
		}

		$value = get_field($key, 'option');

		if ($value === null || $value === false || $value === '') {
			return $default;
		}

		return $value;
	}
}

if (!function_exists('italika_about_promo_shortcode')) {
	function italika_about_promo_shortcode() {

		$title = italika_about_promo_get('about_promo_title');
		$text = italika_about_promo_get('about_promo_text');

		$cards = [
			[
				'value' => italika_about_promo_get('about_promo_card_1_value'),
				'label' => italika_about_promo_get('about_promo_card_1_label'),
			],
			[
				'value' => italika_about_promo_get('about_promo_card_2_value'),
				'label' => italika_about_promo_get('about_promo_card_2_label'),
			],
			[
				'value' => italika_about_promo_get('about_promo_card_3_value'),
				'label' => italika_about_promo_get('about_promo_card_3_label'),
			],
		];

		ob_start();
		?>

<section class="about-promo">
	<div class="container">
		<div class="about-promo__box">
			<div class="about-promo__content">
				<div class="about-promo__eyebrow">О компании</div>

				<?php if ($title !== ''): ?>
					<h2 class="about-promo__title">
						<?php echo esc_html($title); ?>
					</h2>
				<?php endif; ?>

				<?php if ($text !== ''): ?>
					<p class="about-promo__text">
						<?php echo esc_html($text); ?>
					</p>
				<?php endif; ?>

				<div class="about-promo__actions">
					<a class="about-promo__button" href="<?php echo esc_url(home_url('/about/')); ?>">
						Подробнее
					</a>
				</div>
			</div>

			<div class="about-promo__aside">

				<?php foreach ($cards as $card): ?>
					<?php if ($card['value'] !== '' || $card['label'] !== ''): ?>
						<div class="about-promo__stat">
							<span class="about-promo__stat-value">
								<?php echo esc_html($card['value']); ?>
							</span>
							<span class="about-promo__stat-label">
								<?php echo esc_html($card['label']); ?>
							</span>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>

			</div>
		</div>
	</div>
</section>

		<?php

		return ob_get_clean();
	}

	add_shortcode('italika_about_promo', 'italika_about_promo_shortcode');
}