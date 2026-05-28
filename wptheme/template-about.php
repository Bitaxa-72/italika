<?php
/**
 * Template Name: О компании
 * Template Post Type: page
 */

defined('ABSPATH') || exit;

get_header();

if (have_posts()) {
	the_post();
}

$page_title = get_the_title() ?: 'О компании';
$about_assets_uri = get_theme_file_uri('/assets/static/about');

$default_about_content = '<h2>О компании</h2>
<p>С 1997 года наша компания поставляет широкий ассортимент ингредиентов, расходных материалов и инвентаря для производства кондитерских и хлебобулочных изделий, а также известного во всем мире итальянского мороженого. Организация активно работает с сектором HORECA.</p>
<p>Наша компания является дилером холдинга «ITALIKA», жирового итальянского комбината «UNIGRA».</p>
<p>Мы постоянно обновляем ассортимент, отслеживаем современные тенденции развития рынка ингредиентов и инновационных технологий. Регулярно проводим мастер-классы, семинары, в том числе на производстве клиентов.</p>
<p>Наша организация предоставляет комплексные услуги для производства: рецептуры, расчет сырьевой себестоимости готовой продукции, технологические рекомендации, сырье, инвентарь для производства.</p>
<p>Мы всегда открыты к конструктивному диалогу в решении технологических и других вопросов, возникающих в совместной работе.</p>
<h2>Почему именно мы?</h2>
<ol>
<li><strong>Персональный подход.</strong> Менеджер общается с клиентом лично, учитывает его потребности и предпочтения. Условия можно гибко подстраивать под конкретного покупателя.</li>
<li><strong>Экспертная консультация.</strong> Помогаем выбрать товар, сравнить характеристики, подобрать аналоги и получить развернутые ответы на вопросы.</li>
<li><strong>Специальные предложения и скидки.</strong> Доступны индивидуальные бонусы, дополнительные скидки, бесплатная доставка, подарки, акции и предзаказы.</li>
<li><strong>Быстрое решение проблем.</strong> Помогаем с оформлением заказа, возвратами, обменами, контролем доставки и спорными ситуациями.</li>
<li><strong>Лояльность и долгосрочное сотрудничество.</strong> Постоянные клиенты могут рассчитывать на лучшие условия, а менеджер учитывает предпочтения в будущих подборках.</li>
<li><strong>Гибкость в оплате и доставке.</strong> Можно обсудить удобный способ оплаты и выбрать доставку СДЭК, Яндекс Доставкой, Boxberry и другими способами.</li>
<li><strong>Гарантия подлинности и сертификация.</strong> Мы являемся официальными дистрибьюторами и предлагаем оригинальную продукцию с гарантией от производителя. Товары сопровождаются сертификатами качества и документами соответствия.</li>
<li><strong>Прямые поставки от производителя.</strong> Товар поставляется напрямую, без лишних посредников.</li>
<li><strong>Дополнительные проверки перед отправкой.</strong> Склад проверяет товар перед отправкой, в том числе на отсутствие заводских дефектов.</li>
<li><strong>Эксклюзивные знания и навыки.</strong> Мы предлагаем бесплатные обучающие курсы от партнеров-кондитеров: мастер-классы, видеоуроки и пошаговые инструкции по работе с продукцией.</li>
<li><strong>Практические советы от экспертов.</strong> Технологи делятся проверенными рецептами, лайфхаками и секретами профессионального использования товаров.</li>
<li><strong>Поддержка на всех этапах.</strong> Новичкам поможем разобраться в тонкостях кондитерского дела, профессионалам — подобрать продвинутые техники и актуальные тренды.</li>
<li><strong>Широкий ассортимент более 5000 товаров.</strong> Большой выбор от форм для выпечки до высокотехнологичных смесей — все необходимое в одном месте.</li>
<li><strong>Обширная зона доставки.</strong> Доставляем по всей России и работаем с клиентами из Казахстана.</li>
</ol>
<p>Работая с нами, вы убедитесь, что это удобно и выгодно.</p>';

$defaults = [
	'about_page_eyebrow' => 'О компании',
	'about_page_title' => 'Комплексное снабжение для кондитерских, пекарен и HoReCa',
	'about_page_lead' => 'С 1997 года поставляем ингредиенты, расходные материалы и инвентарь для производства кондитерских и хлебобулочных изделий, а также итальянского мороженого.',
	'about_page_stat_1_value' => '1997',
	'about_page_stat_1_label' => 'работаем на рынке',
	'about_page_stat_2_value' => '5000+',
	'about_page_stat_2_label' => 'товаров в ассортименте',
	'about_page_stat_3_value' => 'HoReCa',
	'about_page_stat_3_label' => 'поддержка профессионального сегмента',
	'about_page_content' => $default_about_content,
	'about_page_side_items' => [
		[
			'item_title' => 'Технологическая поддержка',
			'item_text' => 'Рецептуры, расчет сырьевой себестоимости, рекомендации по технологии и помощь с подбором сырья.',
		],
		[
			'item_title' => 'Обучение',
			'item_text' => 'Мастер-классы, семинары и практическая работа на производстве клиентов.',
		],
		[
			'item_title' => 'Поставки',
			'item_text' => 'Ингредиенты, расходные материалы и инвентарь для ежедневного производства.',
		],
	],
	'about_page_docs_eyebrow' => 'Документы',
	'about_page_docs_title' => 'Свидетельства и награды',
	'about_page_docs' => [
		[
			'doc_image' => trailingslashit($about_assets_uri) . '18f9a43abd3c96794b4e183bf8535fdc.jpg',
			'doc_caption' => 'Свидетельство компании',
		],
		[
			'doc_image' => trailingslashit($about_assets_uri) . '58515342b369fa6a62e68cfcec50a809.jpg',
			'doc_caption' => 'Свидетельство компании',
		],
		[
			'doc_image' => trailingslashit($about_assets_uri) . '602230935d7923508f604df4b53c37d0.jpg',
			'doc_caption' => 'Награда компании',
		],
		[
			'doc_image' => trailingslashit($about_assets_uri) . 'a577194fdc3d3b1e0170e3ff28afa939.jpg',
			'doc_caption' => 'Награда компании',
		],
	],
];

$about_get = static function ($key) use ($defaults) {
	$default = array_key_exists($key, $defaults) ? $defaults[$key] : '';
	return function_exists('italika_about_page_get_option') ? italika_about_page_get_option($key, $default) : $default;
};

$get_image_url = static function ($image) {
	if (is_array($image) && !empty($image['url'])) {
		return (string) $image['url'];
	}

	if (is_numeric($image)) {
		$url = wp_get_attachment_image_url((int) $image, 'full');
		return $url ?: '';
	}

	return is_string($image) ? $image : '';
};

$get_image_alt = static function ($image, $fallback = '') {
	if (is_array($image) && !empty($image['alt'])) {
		return (string) $image['alt'];
	}

	return (string) $fallback;
};

$about_eyebrow = $about_get('about_page_eyebrow');
$about_title = $about_get('about_page_title');
$about_lead = $about_get('about_page_lead');
$about_content = $about_get('about_page_content');
$about_side_items = $about_get('about_page_side_items');
$about_docs_eyebrow = $about_get('about_page_docs_eyebrow');
$about_docs_title = $about_get('about_page_docs_title');
$about_docs = $about_get('about_page_docs');

if (!is_array($about_side_items) || empty($about_side_items)) {
	$about_side_items = $defaults['about_page_side_items'];
}

if (!is_array($about_docs) || empty($about_docs)) {
	$about_docs = $defaults['about_page_docs'];
}

$about_stats = [
	[
		'value' => $about_get('about_page_stat_1_value'),
		'label' => $about_get('about_page_stat_1_label'),
	],
	[
		'value' => $about_get('about_page_stat_2_value'),
		'label' => $about_get('about_page_stat_2_label'),
	],
	[
		'value' => $about_get('about_page_stat_3_value'),
		'label' => $about_get('about_page_stat_3_label'),
	],
];

$about_content_rendered = apply_filters('the_content', (string) $about_content);
?>

<div class="breadcrumbs">
	<div class="container">
		<nav class="breadcrumbs__nav" aria-label="Навигация">
			<a class="breadcrumbs__link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a>
			<span class="breadcrumbs__current"><?php echo esc_html($page_title); ?></span>
		</nav>
	</div>
</div>

<section class="about-page">
	<div class="container">
		<div class="about-page__hero">
			<div class="about-page__hero-content">
				<span class="about-page__eyebrow"><?php echo esc_html($about_eyebrow); ?></span>
				<h1 class="about-page__title"><?php echo esc_html($about_title); ?></h1>
				<p class="about-page__lead"><?php echo esc_html($about_lead); ?></p>
			</div>

			<div class="about-page__stats" aria-label="Коротко о компании">
				<?php foreach ($about_stats as $stat) : ?>
					<div class="about-page__stat">
						<strong><?php echo esc_html($stat['value']); ?></strong>
						<span><?php echo esc_html($stat['label']); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="about-page__grid">
			<article class="about-page__content wp-content"><?php echo $about_content_rendered; ?></article>

			<aside class="about-page__side" aria-label="Преимущества">
				<?php foreach ($about_side_items as $item) : ?>
					<?php
					$item_title = isset($item['item_title']) ? (string) $item['item_title'] : '';
					$item_text = isset($item['item_text']) ? (string) $item['item_text'] : '';
					if ($item_title === '' && $item_text === '') {
						continue;
					}
					?>
					<div class="about-page__note">
						<span><?php echo esc_html($item_title); ?></span>
						<p><?php echo esc_html($item_text); ?></p>
					</div>
				<?php endforeach; ?>
			</aside>
		</div>

		<section class="about-page__docs">
			<div class="about-page__section-head">
				<span class="about-page__eyebrow"><?php echo esc_html($about_docs_eyebrow); ?></span>
				<h2><?php echo esc_html($about_docs_title); ?></h2>
			</div>

			<div class="about-page__docs-grid">
				<?php foreach ($about_docs as $doc) : ?>
					<?php
					$doc_image = isset($doc['doc_image']) ? $doc['doc_image'] : '';
					$doc_caption = isset($doc['doc_caption']) ? (string) $doc['doc_caption'] : '';
					$image_url = $get_image_url($doc_image);
					$image_alt = $get_image_alt($doc_image, $doc_caption);

					if ($image_url === '') {
						continue;
					}
					?>
					<a class="about-page__doc" data-fancybox="about-docs" data-caption="<?php echo esc_attr($doc_caption); ?>" href="<?php echo esc_url($image_url); ?>">
						<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>" loading="lazy" decoding="async">
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	</div>
</section>

<?php
get_footer();