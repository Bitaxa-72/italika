<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class('body'); ?>>
<?php wp_body_open(); ?>

<?php
$topbar_left_text = italika_header_get_option('header_topbar_left_text', 'Ингредиенты и инвентарь для кондитерских изделий и мороженого');
$topbar_city = italika_header_get_option('header_topbar_city', 'Екатеринбург');
$topbar_delivery_text = italika_header_get_option('header_topbar_delivery_text', 'Самовывоз и доставка');
$topbar_schedule_weekdays = italika_header_get_option('header_topbar_schedule_weekdays', 'Пн–Пт: с 9:00 до 20:00');
$topbar_schedule_weekend = italika_header_get_option('header_topbar_schedule_weekend', 'Сб, Вс: с 09:00 до 19:00');

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
	$logo_url = get_theme_file_uri('/assets/static/icons/italika-logo.svg');
}

$logo_link = italika_header_get_option('header_logo_link', home_url('/'));

$phone_text = italika_header_get_option('header_phone_text', '+7 (343) 210-88-95');
$phone_label = italika_header_get_option('header_phone_label', 'Заказы и консультации');
$phone_href = italika_header_phone_href($phone_text);
if ($phone_href === '') {
	$phone_href = 'tel:+73432108895';
}

$account_url = function_exists('wc_get_page_permalink')
	? wc_get_page_permalink('myaccount')
	: italika_header_get_option('header_account_url', home_url('/'));
$favorite_url = italika_header_get_option('header_favorite_url', home_url('/favorites/'));
$cart_url = function_exists('wc_get_cart_url')
	? wc_get_cart_url()
	: italika_header_get_option('header_cart_url', home_url('/cart/'));

$favorite_count = italika_header_get_option('header_favorite_count', '2');
$cart_count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : (int) italika_header_get_option('header_cart_count', '0');
?>

<div class="site">
	<div class="topbar">
		<div class="container topbar__inner">
			<div class="topbar__left">
				<?php if ($topbar_left_text !== '') : ?>
					<span><?php echo esc_html($topbar_left_text); ?></span>
				<?php endif; ?>
			</div>

			<div class="topbar__right">
				<?php if ($topbar_city !== '') : ?>
					<span><?php echo esc_html($topbar_city); ?></span>
				<?php endif; ?>

				<?php if ($topbar_delivery_text !== '') : ?>
					<span><?php echo esc_html($topbar_delivery_text); ?></span>
				<?php endif; ?>

				<?php if ($topbar_schedule_weekdays !== '' || $topbar_schedule_weekend !== '') : ?>
					<span class="topbar__schedule">
						<?php if ($topbar_schedule_weekdays !== '') : ?>
							<span><?php echo esc_html($topbar_schedule_weekdays); ?></span>
						<?php endif; ?>

						<?php if ($topbar_schedule_weekend !== '') : ?>
							<span><?php echo esc_html($topbar_schedule_weekend); ?></span>
						<?php endif; ?>
					</span>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<header class="header">
		<div class="container">
			<div class="header__shell">
				<div class="header__inner">
					<a class="logo" href="<?php echo esc_url($logo_link); ?>">
						<img class="logo__svg" height="60" alt="<?php echo esc_attr($logo_alt); ?>" src="<?php echo esc_url($logo_url); ?>">
					</a>

					<div class="header-search">
						<form class="searchbar js-italika-search-form" action="<?php echo esc_url(home_url('/')); ?>" method="get">
							<input class="searchbar__field js-italika-search-input" type="text" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="Поиск товаров" data-placeholder-full="Поиск товаров" data-placeholder-tablet="Поиск товаров" data-placeholder-mobile="Поиск товаров" aria-controls="search-suggest" autocomplete="off">
							<input type="hidden" name="post_type" value="product">
							<button class="searchbar__submit" type="submit" aria-label="Найти"></button>

							<div class="search-suggest js-italika-search-suggest" id="search-suggest" aria-label="Подсказки поиска">
								<div class="search-suggest__head">
									<span>Подсказки</span>
									<a class="js-italika-search-all" href="<?php echo esc_url(home_url('/?post_type=product')); ?>">Все результаты</a>
								</div>

								<div class="search-suggest__list js-italika-search-suggest-list"></div>
							</div>
						</form>

						<button class="nav-toggle" type="button" aria-controls="header-nav" aria-expanded="false">
							<span class="nav-toggle__icon" aria-hidden="true">
								<span class="nav-toggle__line"></span>
								<span class="nav-toggle__line"></span>
								<span class="nav-toggle__line"></span>
							</span>
							<span>Меню</span>
						</button>
					</div>

<div class="header__actions">
	<a class="contact-pill" href="<?php echo esc_url($phone_href); ?>" aria-label="<?php echo esc_attr('Позвонить по номеру ' . $phone_text); ?>">
		<span class="contact-pill__icon" aria-hidden="true">
			<svg viewBox="0 0 24 24" fill="none">
				<path d="M7.2 4.8 9.7 9c.3.5.2 1.1-.2 1.5l-1.1 1.1c1 2 2.6 3.6 4.6 4.6l1.1-1.1c.4-.4 1-.5 1.5-.2l4.2 2.5c.5.3.8.9.6 1.5-.4 1.5-1.8 2.5-3.3 2.5C9.1 21.4 2.6 14.9 2.6 6.9c0-1.5 1-2.9 2.5-3.3.6-.2 1.2.1 1.5.6Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
			</svg>
		</span>
		<strong><?php echo esc_html($phone_text); ?></strong>
		<?php if ($phone_label !== '') : ?>
			<span><?php echo esc_html($phone_label); ?></span>
		<?php endif; ?>
	</a>

	<a class="icon-link icon-link--account js-header-account-trigger" href="<?php echo esc_url($account_url); ?>" aria-label="Личный кабинет">
		<svg viewBox="0 0 24 24" fill="none">
			<circle cx="12" cy="8" r="4" stroke-width="1.8"></circle>
			<path d="M4 20c1.8-3.8 5-5.7 8-5.7S18.2 16.2 20 20" stroke-width="1.8" stroke-linecap="round"></path>
		</svg>
	</a>

<?php
$favorites_count = function_exists('italika_favorites_get_count') ? italika_favorites_get_count() : 0;
$favorites_url = function_exists('italika_favorites_get_archive_url') ? italika_favorites_get_archive_url() : home_url('/favorites/');
?>
<a class="icon-link" href="<?php echo esc_url($favorites_url); ?>" aria-label="Избранное">
	<svg viewBox="0 0 24 24" fill="none">
		<path d="M12 20.2 4.7 13.4A4.9 4.9 0 0 1 11.5 6l.5.6.5-.6a4.9 4.9 0 0 1 6.8 7.3L12 20.2Z" stroke-width="1.8" stroke-linejoin="round"></path>
	</svg>
	<?php if (is_user_logged_in()) : ?>
		<span class="icon-link__count js-favorites-count"><?php echo (int) $favorites_count; ?></span>
	<?php endif; ?>
</a>

	<a class="icon-link js-header-cart" href="<?php echo esc_url($cart_url); ?>" aria-label="Корзина">
		<svg viewBox="0 0 24 24" fill="none">
			<path d="M3.5 5H6l1.6 8.1c.1.7.7 1.2 1.4 1.2h7.7c.7 0 1.3-.5 1.4-1.2L19.5 8H7.1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
			<circle cx="10" cy="18.2" r="1.2" fill="currentColor" stroke="none"></circle>
			<circle cx="17" cy="18.2" r="1.2" fill="currentColor" stroke="none"></circle>
		</svg>
		<span class="icon-link__count js-cart-count" <?php echo $cart_count > 0 ? '' : 'hidden'; ?>><?php echo (int) $cart_count; ?></span>
	</a>
</div>
				</div>

				<nav class="nav" id="header-nav">
					<div class="nav__scroll">
						<div class="nav__list">
							<?php
							wp_nav_menu([
								'theme_location' => 'header_menu',
								'container'      => false,
								'items_wrap'     => '%3$s',
								'depth'          => 1,
								'fallback_cb'    => false,
								'walker'         => new Italika_Header_Menu_Walker(),
							]);
							?>
						</div>
					</div>
				</nav>
			</div>
		</div>
	</header>
	
    <main class="site-main">	
	<?php
	if (function_exists('italika_seo_render_breadcrumbs')) {
		italika_seo_render_breadcrumbs();
	}
	?>
