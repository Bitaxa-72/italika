<?php
/**
 * Template Name: Контакты
 * Template Post Type: page
 */

defined('ABSPATH') || exit;

get_header();

if (have_posts()) {
	the_post();
}

$page_title = get_the_title() ?: 'Контакты';

$defaults = [
	'contacts_page_eyebrow' => 'Контактная информация',
	'contacts_page_title' => 'Контакты',
	'contacts_page_summary' => 'Магазин, самовывоз, доставка заказов интернет-магазина, оптовые поставки и вопросы для юридических лиц.',
	'contacts_page_retail_section_title' => 'Розничный магазин и самовывоз',
	'contacts_page_retail_address_label' => 'Адрес',
	'contacts_page_retail_address' => 'г. Екатеринбург, ул. 8 Марта 212, ДК РТИ, центральный вход-2, офис 123',
	'contacts_page_retail_cash_label' => 'Касса розничного магазина',
	'contacts_page_retail_cash_phone' => '+7 (343) 210-88-95',
	'contacts_page_retail_pickup_label' => 'Самовывоз заказов, интернет-магазин, наличие товара',
	'contacts_page_retail_pickup_phone_1' => '+7 (343) 210-88-95',
	'contacts_page_retail_pickup_phone_2' => '+7 (902) 449-94-47',
	'contacts_page_delivery_section_title' => 'Доставка',
	'contacts_page_delivery_city_label' => 'Доставка заказов интернет-магазина по Екатеринбургу',
	'contacts_page_delivery_city_phone' => '+7 (950) 553-42-08',
	'contacts_page_delivery_city_person' => 'Наталия',
	'contacts_page_delivery_intercity_label' => 'Доставка межгород и до транспортной компании',
	'contacts_page_delivery_intercity_phone' => '+7 (992) 346-57-89',
	'contacts_page_delivery_intercity_person' => 'Мария',
	'contacts_page_map_section_title' => 'На карте',
	'contacts_page_map_aria_label' => 'Карта проезда до магазина Италика',
	'contacts_page_map_src' => 'https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A04bbe51bd5a436f6e1d422743e6c77e276273d8b7f507b1d2c12e5040db29f22&width=100%25&height=422&lang=ru_RU&scroll=true',
	'contacts_page_map_height' => 422,
	'contacts_page_shop_section_title' => 'Интернет-магазин',
	'contacts_page_shop_phone_label' => 'Телефон',
	'contacts_page_shop_phone' => '+7 (902) 449-94-47',
	'contacts_page_shop_email_label' => 'Почта',
	'contacts_page_shop_email' => 'allise-italika@mail.ru',
	'contacts_page_wholesale_section_title' => 'Оптовые поставки, юр. лица',
	'contacts_page_wholesale_phones_label' => 'Телефоны',
	'contacts_page_wholesale_phone_1' => '+7 (343) 379-01-13',
	'contacts_page_wholesale_phone_2' => '+7 (343) 379-01-14',
	'contacts_page_wholesale_phone_3' => '+7 (950) 553-41-55',
	'contacts_page_wholesale_email_label' => 'Почта',
	'contacts_page_wholesale_email' => 'olio@inbox.ru',
	'contacts_page_social_section_title' => 'Мы в социальных сетях',
	'contacts_page_social_text' => 'Италика Урал ВКонтакте',
	'contacts_page_social_url' => '',
];

$contacts_get = static function ($key) use ($defaults) {
	$default = array_key_exists($key, $defaults) ? $defaults[$key] : '';
	return function_exists('italika_contacts_page_get_option') ? italika_contacts_page_get_option($key, $default) : $default;
};

$phone_href = static function ($phone) {
	if (function_exists('italika_header_phone_href')) {
		return italika_header_phone_href($phone);
	}

	$phone = preg_replace('/[^0-9\+]/', '', (string) $phone);
	return $phone ? 'tel:' . $phone : '';
};

$email_href = static function ($email) {
	if (function_exists('italika_email_href')) {
		return italika_email_href($email);
	}

	$email = sanitize_email((string) $email);
	return $email ? 'mailto:' . $email : '';
};

$map_height = absint($contacts_get('contacts_page_map_height')) ?: 422;
$map_src = remove_query_arg(['width', 'height'], (string) $contacts_get('contacts_page_map_src'));
$map_src = add_query_arg([
	'width' => '100%',
	'height' => $map_height,
], $map_src);
?>

<div class="breadcrumbs">
	<div class="container">
		<nav class="breadcrumbs__nav" aria-label="Навигация">
			<a class="breadcrumbs__link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a>
			<span class="breadcrumbs__current"><?php echo esc_html($page_title); ?></span>
		</nav>
	</div>
</div>

<section class="contacts-page">
	<div class="container">
		<div class="contacts-page__head">
			<div class="contacts-page__intro">
				<span class="contacts-page__eyebrow"><?php echo esc_html($contacts_get('contacts_page_eyebrow')); ?></span>
				<h1 class="contacts-page__title"><?php echo esc_html($contacts_get('contacts_page_title')); ?></h1>
				<p class="contacts-page__summary"><?php echo esc_html($contacts_get('contacts_page_summary')); ?></p>
			</div>
		</div>

		<div class="contacts-page__layout">
			<div class="contacts-page__main">
				<section class="contacts-page__panel">
					<h2 class="contacts-page__panel-title"><?php echo esc_html($contacts_get('contacts_page_retail_section_title')); ?></h2>
					<div class="contacts-page__items">
						<article class="contacts-page__item">
							<span class="contacts-page__label"><?php echo esc_html($contacts_get('contacts_page_retail_address_label')); ?></span>
							<p><?php echo nl2br(esc_html($contacts_get('contacts_page_retail_address'))); ?></p>
						</article>
						<article class="contacts-page__item">
							<span class="contacts-page__label"><?php echo esc_html($contacts_get('contacts_page_retail_cash_label')); ?></span>
							<?php $retail_cash_phone = $contacts_get('contacts_page_retail_cash_phone'); ?>
							<a href="<?php echo esc_url($phone_href($retail_cash_phone)); ?>"><?php echo esc_html($retail_cash_phone); ?></a>
						</article>
						<article class="contacts-page__item">
							<span class="contacts-page__label"><?php echo esc_html($contacts_get('contacts_page_retail_pickup_label')); ?></span>
							<p>
								<?php $retail_pickup_phone_1 = $contacts_get('contacts_page_retail_pickup_phone_1'); ?>
								<?php $retail_pickup_phone_2 = $contacts_get('contacts_page_retail_pickup_phone_2'); ?>
								<?php if ($retail_pickup_phone_1 !== '') : ?>
									<a href="<?php echo esc_url($phone_href($retail_pickup_phone_1)); ?>"><?php echo esc_html($retail_pickup_phone_1); ?></a>
								<?php endif; ?>
								<?php if ($retail_pickup_phone_2 !== '') : ?>
									<a href="<?php echo esc_url($phone_href($retail_pickup_phone_2)); ?>"><?php echo esc_html($retail_pickup_phone_2); ?></a>
								<?php endif; ?>
							</p>
						</article>
					</div>
				</section>

				<section class="contacts-page__panel">
					<h2 class="contacts-page__panel-title"><?php echo esc_html($contacts_get('contacts_page_delivery_section_title')); ?></h2>
					<div class="contacts-page__items">
						<article class="contacts-page__item">
							<span class="contacts-page__label"><?php echo esc_html($contacts_get('contacts_page_delivery_city_label')); ?></span>
							<p>
								<?php $delivery_city_phone = $contacts_get('contacts_page_delivery_city_phone'); ?>
								<a href="<?php echo esc_url($phone_href($delivery_city_phone)); ?>"><?php echo esc_html($delivery_city_phone); ?></a>
								<?php $delivery_city_person = $contacts_get('contacts_page_delivery_city_person'); ?>
								<?php if ($delivery_city_person !== '') : ?>
									<?php echo ' ' . esc_html($delivery_city_person); ?>
								<?php endif; ?>
							</p>
						</article>
						<article class="contacts-page__item">
							<span class="contacts-page__label"><?php echo esc_html($contacts_get('contacts_page_delivery_intercity_label')); ?></span>
							<p>
								<?php $delivery_intercity_phone = $contacts_get('contacts_page_delivery_intercity_phone'); ?>
								<a href="<?php echo esc_url($phone_href($delivery_intercity_phone)); ?>"><?php echo esc_html($delivery_intercity_phone); ?></a>
								<?php $delivery_intercity_person = $contacts_get('contacts_page_delivery_intercity_person'); ?>
								<?php if ($delivery_intercity_person !== '') : ?>
									<?php echo ' ' . esc_html($delivery_intercity_person); ?>
								<?php endif; ?>
							</p>
						</article>
					</div>
				</section>

				<section class="contacts-page__panel contacts-page__panel--wide">
					<h2 class="contacts-page__panel-title"><?php echo esc_html($contacts_get('contacts_page_map_section_title')); ?></h2>
					<div class="contacts-page__map" aria-label="<?php echo esc_attr($contacts_get('contacts_page_map_aria_label')); ?>">
						<script type="text/javascript" charset="utf-8" async src="<?php echo esc_url($map_src); ?>"></script>
					</div>
				</section>
			</div>

			<aside class="contacts-page__side" aria-label="Дополнительные контакты">
				<section class="contacts-page__panel">
					<h2 class="contacts-page__panel-title"><?php echo esc_html($contacts_get('contacts_page_shop_section_title')); ?></h2>
					<div class="contacts-page__items">
						<article class="contacts-page__item">
							<span class="contacts-page__label"><?php echo esc_html($contacts_get('contacts_page_shop_phone_label')); ?></span>
							<?php $shop_phone = $contacts_get('contacts_page_shop_phone'); ?>
							<a href="<?php echo esc_url($phone_href($shop_phone)); ?>"><?php echo esc_html($shop_phone); ?></a>
						</article>
						<article class="contacts-page__item">
							<span class="contacts-page__label"><?php echo esc_html($contacts_get('contacts_page_shop_email_label')); ?></span>
							<?php $shop_email = $contacts_get('contacts_page_shop_email'); ?>
							<a href="<?php echo esc_url($email_href($shop_email)); ?>"><?php echo esc_html($shop_email); ?></a>
						</article>
					</div>
				</section>

				<section class="contacts-page__panel">
					<h2 class="contacts-page__panel-title"><?php echo esc_html($contacts_get('contacts_page_wholesale_section_title')); ?></h2>
					<div class="contacts-page__items">
						<article class="contacts-page__item">
							<span class="contacts-page__label"><?php echo esc_html($contacts_get('contacts_page_wholesale_phones_label')); ?></span>
							<p>
								<?php $wholesale_phone_1 = $contacts_get('contacts_page_wholesale_phone_1'); ?>
								<?php $wholesale_phone_2 = $contacts_get('contacts_page_wholesale_phone_2'); ?>
								<?php $wholesale_phone_3 = $contacts_get('contacts_page_wholesale_phone_3'); ?>
								<?php if ($wholesale_phone_1 !== '') : ?>
									<a href="<?php echo esc_url($phone_href($wholesale_phone_1)); ?>"><?php echo esc_html($wholesale_phone_1); ?></a>
								<?php endif; ?>
								<?php if ($wholesale_phone_2 !== '') : ?>
									<a href="<?php echo esc_url($phone_href($wholesale_phone_2)); ?>"><?php echo esc_html($wholesale_phone_2); ?></a>
								<?php endif; ?>
								<?php if ($wholesale_phone_3 !== '') : ?>
									<a href="<?php echo esc_url($phone_href($wholesale_phone_3)); ?>"><?php echo esc_html($wholesale_phone_3); ?></a>
								<?php endif; ?>
							</p>
						</article>
						<article class="contacts-page__item">
							<span class="contacts-page__label"><?php echo esc_html($contacts_get('contacts_page_wholesale_email_label')); ?></span>
							<?php $wholesale_email = $contacts_get('contacts_page_wholesale_email'); ?>
							<a href="<?php echo esc_url($email_href($wholesale_email)); ?>"><?php echo esc_html($wholesale_email); ?></a>
						</article>
					</div>
				</section>

				<section class="contacts-page__panel">
					<h2 class="contacts-page__panel-title"><?php echo esc_html($contacts_get('contacts_page_social_section_title')); ?></h2>
					<?php $social_url = $contacts_get('contacts_page_social_url'); ?>
					<?php if ($social_url !== '') : ?>
						<a class="contacts-page__social" href="<?php echo esc_url($social_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($contacts_get('contacts_page_social_text')); ?></a>
					<?php else : ?>
						<span class="contacts-page__social"><?php echo esc_html($contacts_get('contacts_page_social_text')); ?></span>
					<?php endif; ?>
				</section>
			</aside>
		</div>
	</div>
</section>

<?php
get_footer();