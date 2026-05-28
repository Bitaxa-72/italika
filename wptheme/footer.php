<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

</main>

<?php
$logo = italika_header_get_option('header_logo');

$logo_url = '';
$logo_alt = get_bloginfo('name');

if (is_array($logo)) {
	$logo_url = !empty($logo['url']) ? $logo['url'] : '';
	$logo_alt = !empty($logo['alt']) ? $logo['alt'] : $logo_alt;
} elseif (is_numeric($logo)) {
	$logo_url = wp_get_attachment_image_url((int) $logo, 'full');
	$logo_alt = get_post_meta((int) $logo, '_wp_attachment_image_alt', true) ?: $logo_alt;
} elseif (is_string($logo)) {
	$logo_url = $logo;
}

if ($logo_url === '') {
	$logo_url = get_theme_file_uri('/assets/static/icons/italika-logo-fot.svg');
}

$footer_description = function_exists('get_field') ? get_field('footer_description', 'option') : '';

$catalog = function_exists('get_field') ? get_field('footer_catalog_links', 'option') : [];
$info = function_exists('get_field') ? get_field('footer_info_links', 'option') : [];
$company = function_exists('get_field') ? get_field('footer_company_links', 'option') : [];

$phone = function_exists('get_field') ? get_field('footer_phone', 'option') : '';
$email = function_exists('get_field') ? get_field('footer_email', 'option') : '';
$address = function_exists('get_field') ? get_field('footer_address', 'option') : '';

$schedule_weekdays = function_exists('get_field') ? get_field('footer_schedule_weekdays', 'option') : '';
$schedule_weekend = function_exists('get_field') ? get_field('footer_schedule_weekend', 'option') : '';
?>

<footer class="footer">
	<div class="container">
		<div class="footer__grid">
			<div class="footer__logo">
				<a class="logo" href="<?php echo esc_url(home_url('/')); ?>">
					<img class="logo__svg" height="60" alt="<?php echo esc_attr($logo_alt); ?>" src="<?php echo esc_url($logo_url); ?>">
				</a>

				<?php if ($footer_description !== '') : ?>
					<span><?php echo esc_html($footer_description); ?></span>
				<?php endif; ?>
			</div>

			<div class="footer__col">
				<h3 class="footer__title">Каталог</h3>
				<div class="footer__list">
					<?php if (is_array($catalog)) : ?>
						<?php foreach ($catalog as $row) : ?>
							<?php
							$text = isset($row['text']) ? trim((string) $row['text']) : '';
							$link = '';
							if (isset($row['link']) && is_array($row['link']) && !empty($row['link']['url'])) {
								$link = (string) $row['link']['url'];
							}
							?>
							<?php if ($text !== '' && $link !== '' && (!function_exists('italika_posts_archive_should_hide_link') || !italika_posts_archive_should_hide_link($link))) : ?>
								<a href="<?php echo esc_url($link); ?>"><?php echo esc_html($text); ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="footer__col">
				<h3 class="footer__title">Информация</h3>
				<div class="footer__list">
					<?php if (is_array($info)) : ?>
						<?php foreach ($info as $row) : ?>
							<?php
							$text = isset($row['text']) ? trim((string) $row['text']) : '';
							$link = '';
							if (isset($row['link']) && is_array($row['link']) && !empty($row['link']['url'])) {
								$link = (string) $row['link']['url'];
							}
							?>
							<?php if ($text !== '' && $link !== '' && (!function_exists('italika_posts_archive_should_hide_link') || !italika_posts_archive_should_hide_link($link))) : ?>
								<a href="<?php echo esc_url($link); ?>"><?php echo esc_html($text); ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="footer__col">
				<h3 class="footer__title">Компания</h3>
				<div class="footer__list">
					<?php if (is_array($company)) : ?>
						<?php foreach ($company as $row) : ?>
							<?php
							$text = isset($row['text']) ? trim((string) $row['text']) : '';
							$link = '';
							if (isset($row['link']) && is_array($row['link']) && !empty($row['link']['url'])) {
								$link = (string) $row['link']['url'];
							}
							?>
							<?php if ($text !== '' && $link !== '' && (!function_exists('italika_posts_archive_should_hide_link') || !italika_posts_archive_should_hide_link($link))) : ?>
								<a href="<?php echo esc_url($link); ?>"><?php echo esc_html($text); ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="footer__col">
				<h3 class="footer__title">Контакты</h3>
				<div class="footer__list">
					<?php if ($phone !== '') : ?>
						<span><?php echo esc_html($phone); ?></span>
					<?php endif; ?>

					<?php if ($email !== '') : ?>
						<span><?php echo esc_html($email); ?></span>
					<?php endif; ?>

					<?php if ($address !== '') : ?>
						<span><?php echo esc_html($address); ?></span>
					<?php endif; ?>

					<?php if ($schedule_weekdays !== '') : ?>
						<span><?php echo esc_html($schedule_weekdays); ?></span>
					<?php endif; ?>

					<?php if ($schedule_weekend !== '') : ?>
						<span><?php echo esc_html($schedule_weekend); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="footer__bottom">
			<span>© <?php echo esc_html(wp_date('Y')); ?> ITALIKA. Все права защищены.</span>
			<span>Интернет-магазин для кондитеров, пекарен и HoReCa</span>
		</div>
	</div>
</footer>

<div class="subscribe-modal" data-subscribe-modal aria-hidden="true">
	<div class="subscribe-modal__overlay" data-subscribe-close></div>

	<section class="subscribe-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="subscribe-modal-title" aria-describedby="subscribe-modal-text">
		<button class="subscribe-modal__close" type="button" data-subscribe-close aria-label="Закрыть окно"></button>

		<div class="subscribe-modal__media" aria-hidden="true">
			<span class="subscribe-modal__icon">
				<svg viewBox="0 0 24 24" fill="none">
					<path d="M4.5 6.5h15v11h-15v-11Z" stroke-width="1.8" stroke-linejoin="round"></path>
					<path d="m5 7 7 6 7-6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
				</svg>
			</span>
		</div>

		<div class="subscribe-modal__content">
			<p class="subscribe-modal__eyebrow">Рассылка Italika</p>
			<h2 class="subscribe-modal__title" id="subscribe-modal-title">Новости и рецепты на почту</h2>
			<p class="subscribe-modal__text" id="subscribe-modal-text">Отправляем полезные подборки.</p>

			<form class="subscribe-modal__form js-italika-newsletter-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
				<label class="subscribe-modal__label" for="subscribe-email">Email</label>
				<div class="subscribe-modal__field">
					<input class="subscribe-modal__input" id="subscribe-email" name="email" type="email" placeholder="mail@example.ru" autocomplete="email" required>
					<button class="subscribe-modal__submit" type="submit">Подписаться</button>
				</div>
				<?php if (function_exists('italika_spam_render_fields')) : ?>
					<?php italika_spam_render_fields('newsletter'); ?>
				<?php endif; ?>
				<p class="subscribe-modal__message js-italika-newsletter-message" hidden></p>
				<p class="subscribe-modal__note">Нажимая кнопку, вы соглашаетесь получать письма от Italika. Отписаться можно в любой момент.</p>
			</form>
		</div>
	</section>
</div>

</div>

<?php wp_footer(); ?>
</body>
</html>
