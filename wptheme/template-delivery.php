<?php
/**
 * Template Name: Доставка и оплата
 * Template Post Type: page
 */

defined('ABSPATH') || exit;

get_header();

if (have_posts()) {
	the_post();
}

$page_title = get_the_title() ?: 'Доставка и оплата';

$defaults = [
	'delivery_page_eyebrow' => 'Информация для клиентов',
	'delivery_page_title' => 'Доставка и оплата',
	'delivery_page_summary' => "Заказы принимаются круглосуточно через корзину интернет-магазина.\nМинимальная сумма заказа не ограничена.",
	'delivery_page_payment_section_title' => 'Оплата и магазин',
	'delivery_page_payment_methods_label' => 'Способы оплаты',
	'delivery_page_payment_methods_text' => 'Наличный расчет, безналичная оплата, оплата на расчетный счет.',
	'delivery_page_shop_address_label' => 'Адрес магазина',
	'delivery_page_shop_address_text' => 'г. Екатеринбург, ул. 8 Марта 212, подъезд 2.',
	'delivery_page_shop_hours_text' => 'Пн-пт: с 9:00 до 20:00, сб-вск: с 9:00 до 19:00.',
	'delivery_page_pickup_section_title' => 'Самовывоз',
	'delivery_page_pickup_step_1' => 'Вы можете сделать заказ через корзину интернет-магазина и самостоятельно забрать заказ из магазина.',
	'delivery_page_pickup_step_2' => 'Конкретное время готовности заказа вам сообщит менеджер по телефону.',
	'delivery_page_pickup_urgent_text' => 'При необходимости срочно получить заказ позвоните менеджеру:',
	'delivery_page_pickup_urgent_phone_1' => '8 (902) 449-94-47',
	'delivery_page_pickup_urgent_phone_2' => '8 (343) 210-88-95',
	'delivery_page_pickup_step_4' => 'Хранение собранного заказа - 3 дня.',
	'delivery_page_city_section_title' => 'Доставка по Екатеринбургу и близлежащим городам',
	'delivery_page_city_card_label' => 'Интернет-магазин',
	'delivery_page_city_text_prefix' => 'Доставка по городу:',
	'delivery_page_city_phone' => '8 (950) 553-42-08',
	'delivery_page_city_person' => 'Наталия',
	'delivery_page_intercity_card_label' => 'Межгород',
	'delivery_page_intercity_text_prefix' => 'Доставка межгород:',
	'delivery_page_intercity_phone' => '8 (992) 346-57-89',
	'delivery_page_intercity_person' => 'Мария',
	'delivery_page_russia_section_title' => 'Доставка во все регионы России',
	'delivery_page_russia_steps' => [
		[
			'step_text' => 'Обработка, согласование заказа и формирование счета - в течение 1-2 рабочих дней, пн-пт с 9:00 до 17:00.',
		],
		[
			'step_text' => 'Отправка в транспортную компанию или на Почту РФ - в течение 1-2 рабочих дней после получения оплаты заказа. С наложенными платежами не работаем.',
		],
		[
			'step_text' => 'Клиент обязательно указывает ФИО получателя, контактный телефон, e-mail и адрес доставки. Способ доставки указывается в комментариях.',
		],
	],
	'delivery_page_tariffs' => [
		[
			'tariff_title' => 'Для физических лиц',
			'tariff_line_1' => 'До 3000 руб. - 250 руб.',
			'tariff_line_2' => 'От 3000 руб. - бесплатно.',
		],
		[
			'tariff_title' => 'Для юридических лиц',
			'tariff_line_1' => 'До 5000 руб. - 300 руб.',
			'tariff_line_2' => 'От 5000 руб. - бесплатно.',
		],
	],
	'delivery_page_note_section_title' => 'Примечание',
	'delivery_page_note_steps' => [
		[
			'step_text' => 'Выбор транспортной компании или Почты РФ клиент производит самостоятельно с учетом затрат на доставку.',
		],
		[
			'step_text' => 'Настоятельно рекомендуем использовать палет-борт или обрешетку для сохранности груза.',
		],
		[
			'step_text' => 'В случае отказа от палет-борта сообщите нам об этом в заявке, в комментариях к заказу.',
		],
	],
	'delivery_page_side_contacts_title' => 'Контакты доставки',
	'delivery_page_side_city_label' => 'По городу',
	'delivery_page_side_intercity_label' => 'Межгород',
	'delivery_page_faq_title' => 'FAQ',
	'delivery_page_faq_items' => [
		[
			'faq_question' => 'Как быстро соберут заказ?',
			'faq_answer' => 'Заглушка для будущего ответа.',
		],
		[
			'faq_question' => 'Можно ли выбрать транспортную компанию?',
			'faq_answer' => 'Заглушка для будущего ответа.',
		],
		[
			'faq_question' => 'Как оплатить заказ на расчетный счет?',
			'faq_answer' => 'Заглушка для будущего ответа.',
		],
		[
			'faq_question' => 'Что делать, если заказ нужен срочно?',
			'faq_answer' => 'Заглушка для будущего ответа.',
		],
	],
];

$delivery_get = static function ($key) use ($defaults) {
	$default = array_key_exists($key, $defaults) ? $defaults[$key] : '';
	return function_exists('italika_delivery_page_get_option') ? italika_delivery_page_get_option($key, $default) : $default;
};

$phone_href = static function ($phone) {
	if (function_exists('italika_header_phone_href')) {
		return italika_header_phone_href($phone);
	}

	$phone = preg_replace('/[^0-9\+]/', '', (string) $phone);
	return $phone ? 'tel:' . $phone : '';
};

$normalize_rows = static function ($rows, $fallback) {
	if (!is_array($rows) || empty($rows)) {
		return $fallback;
	}

	return $rows;
};

$delivery_summary = $delivery_get('delivery_page_summary');
$russia_steps = $normalize_rows($delivery_get('delivery_page_russia_steps'), $defaults['delivery_page_russia_steps']);
$tariffs = $normalize_rows($delivery_get('delivery_page_tariffs'), $defaults['delivery_page_tariffs']);
$note_steps = $normalize_rows($delivery_get('delivery_page_note_steps'), $defaults['delivery_page_note_steps']);
$faq_items = $normalize_rows($delivery_get('delivery_page_faq_items'), $defaults['delivery_page_faq_items']);

$pickup_urgent_phone_1 = $delivery_get('delivery_page_pickup_urgent_phone_1');
$pickup_urgent_phone_2 = $delivery_get('delivery_page_pickup_urgent_phone_2');
$city_phone = $delivery_get('delivery_page_city_phone');
$intercity_phone = $delivery_get('delivery_page_intercity_phone');
?>

<div class="breadcrumbs">
	<div class="container">
		<nav class="breadcrumbs__nav" aria-label="Навигация">
			<a class="breadcrumbs__link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a>
			<span class="breadcrumbs__current"><?php echo esc_html($page_title); ?></span>
		</nav>
	</div>
</div>

<section class="delivery-page">
	<div class="container">
		<div class="delivery-page__head">
			<div class="delivery-page__intro">
				<span class="delivery-page__eyebrow"><?php echo esc_html($delivery_get('delivery_page_eyebrow')); ?></span>
				<h1 class="delivery-page__title"><?php echo esc_html($delivery_get('delivery_page_title')); ?></h1>
				<p class="delivery-page__summary"><?php echo nl2br(esc_html($delivery_summary)); ?></p>
			</div>
		</div>

		<div class="delivery-page__layout">
			<div class="delivery-page__main">
				<section class="delivery-page__panel">
					<h2 class="delivery-page__panel-title"><?php echo esc_html($delivery_get('delivery_page_payment_section_title')); ?></h2>
					<div class="delivery-page__cards">
						<article class="delivery-page__card">
							<span class="delivery-page__label"><?php echo esc_html($delivery_get('delivery_page_payment_methods_label')); ?></span>
							<p><?php echo nl2br(esc_html($delivery_get('delivery_page_payment_methods_text'))); ?></p>
						</article>
						<article class="delivery-page__card">
							<span class="delivery-page__label"><?php echo esc_html($delivery_get('delivery_page_shop_address_label')); ?></span>
							<p><?php echo nl2br(esc_html($delivery_get('delivery_page_shop_address_text'))); ?></p>
							<p><?php echo nl2br(esc_html($delivery_get('delivery_page_shop_hours_text'))); ?></p>
						</article>
					</div>
				</section>

				<section class="delivery-page__panel">
					<h2 class="delivery-page__panel-title"><?php echo esc_html($delivery_get('delivery_page_pickup_section_title')); ?></h2>
					<ol class="delivery-page__steps">
						<li><?php echo nl2br(esc_html($delivery_get('delivery_page_pickup_step_1'))); ?></li>
						<li><?php echo nl2br(esc_html($delivery_get('delivery_page_pickup_step_2'))); ?></li>
						<li>
							<?php echo esc_html($delivery_get('delivery_page_pickup_urgent_text')); ?>
							<a href="<?php echo esc_url($phone_href($pickup_urgent_phone_1)); ?>"><?php echo esc_html($pickup_urgent_phone_1); ?></a>,
							<a href="<?php echo esc_url($phone_href($pickup_urgent_phone_2)); ?>"><?php echo esc_html($pickup_urgent_phone_2); ?></a>.
						</li>
						<li><?php echo nl2br(esc_html($delivery_get('delivery_page_pickup_step_4'))); ?></li>
					</ol>
				</section>

				<section class="delivery-page__panel">
					<h2 class="delivery-page__panel-title"><?php echo esc_html($delivery_get('delivery_page_city_section_title')); ?></h2>
					<div class="delivery-page__cards">
						<article class="delivery-page__card">
							<span class="delivery-page__label"><?php echo esc_html($delivery_get('delivery_page_city_card_label')); ?></span>
							<p>
								<?php echo esc_html($delivery_get('delivery_page_city_text_prefix')); ?>
								<a href="<?php echo esc_url($phone_href($city_phone)); ?>"><?php echo esc_html($city_phone); ?></a>,
								<?php echo esc_html($delivery_get('delivery_page_city_person')); ?>.
							</p>
						</article>
						<article class="delivery-page__card">
							<span class="delivery-page__label"><?php echo esc_html($delivery_get('delivery_page_intercity_card_label')); ?></span>
							<p>
								<?php echo esc_html($delivery_get('delivery_page_intercity_text_prefix')); ?>
								<a href="<?php echo esc_url($phone_href($intercity_phone)); ?>"><?php echo esc_html($intercity_phone); ?></a>,
								<?php echo esc_html($delivery_get('delivery_page_intercity_person')); ?>.
							</p>
						</article>
					</div>
				</section>

				<section class="delivery-page__panel">
					<h2 class="delivery-page__panel-title"><?php echo esc_html($delivery_get('delivery_page_russia_section_title')); ?></h2>
					<ol class="delivery-page__steps">
						<?php foreach ($russia_steps as $step) : ?>
							<?php $step_text = isset($step['step_text']) ? (string) $step['step_text'] : ''; ?>
							<?php if ($step_text === '') { continue; } ?>
							<li><?php echo nl2br(esc_html($step_text)); ?></li>
						<?php endforeach; ?>
					</ol>

					<div class="delivery-page__tariffs">
						<?php foreach ($tariffs as $tariff) : ?>
							<?php
							$tariff_title = isset($tariff['tariff_title']) ? (string) $tariff['tariff_title'] : '';
							$tariff_line_1 = isset($tariff['tariff_line_1']) ? (string) $tariff['tariff_line_1'] : '';
							$tariff_line_2 = isset($tariff['tariff_line_2']) ? (string) $tariff['tariff_line_2'] : '';
							if ($tariff_title === '' && $tariff_line_1 === '' && $tariff_line_2 === '') {
								continue;
							}
							?>
							<article class="delivery-page__tariff">
								<h3><?php echo esc_html($tariff_title); ?></h3>
								<?php if ($tariff_line_1 !== '') : ?>
									<p><?php echo esc_html($tariff_line_1); ?></p>
								<?php endif; ?>
								<?php if ($tariff_line_2 !== '') : ?>
									<p><?php echo esc_html($tariff_line_2); ?></p>
								<?php endif; ?>
							</article>
						<?php endforeach; ?>
					</div>
				</section>

				<section class="delivery-page__panel">
					<h2 class="delivery-page__panel-title"><?php echo esc_html($delivery_get('delivery_page_note_section_title')); ?></h2>
					<ol class="delivery-page__steps">
						<?php foreach ($note_steps as $step) : ?>
							<?php $step_text = isset($step['step_text']) ? (string) $step['step_text'] : ''; ?>
							<?php if ($step_text === '') { continue; } ?>
							<li><?php echo nl2br(esc_html($step_text)); ?></li>
						<?php endforeach; ?>
					</ol>
				</section>
			</div>

			<aside class="delivery-page__side" aria-label="Краткая информация">
				<section class="delivery-page__panel">
					<h2 class="delivery-page__panel-title"><?php echo esc_html($delivery_get('delivery_page_side_contacts_title')); ?></h2>
					<div class="delivery-page__cards">
						<article class="delivery-page__card">
							<span class="delivery-page__label"><?php echo esc_html($delivery_get('delivery_page_side_city_label')); ?></span>
							<a href="<?php echo esc_url($phone_href($city_phone)); ?>"><?php echo esc_html($city_phone); ?></a>
							<p><?php echo esc_html($delivery_get('delivery_page_city_person')); ?></p>
						</article>
						<article class="delivery-page__card">
							<span class="delivery-page__label"><?php echo esc_html($delivery_get('delivery_page_side_intercity_label')); ?></span>
							<a href="<?php echo esc_url($phone_href($intercity_phone)); ?>"><?php echo esc_html($intercity_phone); ?></a>
							<p><?php echo esc_html($delivery_get('delivery_page_intercity_person')); ?></p>
						</article>
					</div>
				</section>

				<section class="delivery-page__panel delivery-page__faq">
					<h2 class="delivery-page__panel-title"><?php echo esc_html($delivery_get('delivery_page_faq_title')); ?></h2>
					<?php foreach ($faq_items as $item) : ?>
						<?php
						$faq_question = isset($item['faq_question']) ? (string) $item['faq_question'] : '';
						$faq_answer = isset($item['faq_answer']) ? (string) $item['faq_answer'] : '';
						if ($faq_question === '' && $faq_answer === '') {
							continue;
						}
						?>
						<details class="delivery-page__accordion">
							<summary><?php echo esc_html($faq_question); ?></summary>
							<p><?php echo nl2br(esc_html($faq_answer)); ?></p>
						</details>
					<?php endforeach; ?>
				</section>
			</aside>
		</div>
	</div>
</section>

<?php
get_footer();