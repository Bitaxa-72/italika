<?php
defined('ABSPATH') || exit;

get_header();

$show_news_link = function_exists('italika_posts_archive_has_published_posts') ? italika_posts_archive_has_published_posts('news') : true;
$show_recipes_link = function_exists('italika_posts_archive_has_published_posts') ? italika_posts_archive_has_published_posts('recipes') : true;
?>

<section class="not-found-page">
	<div class="container">
		<div class="not-found-page__grid">
			<div class="not-found-page__content">
				<p class="not-found-page__eyebrow">404</p>
				<h1 class="not-found-page__title">Страница не найдена</h1>
				<p class="not-found-page__text">
					Похоже, ссылка устарела или адрес набран с ошибкой. Можно вернуться на главную, открыть каталог или посмотреть полезные материалы Italika.
				</p>
				<div class="not-found-page__actions">
					<a class="not-found-page__button" href="<?php echo esc_url(home_url('/')); ?>">На главную</a>
					<a class="not-found-page__button not-found-page__button--ghost" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/')); ?>">В каталог</a>
				</div>
			</div>

			<aside class="not-found-page__panel" aria-label="Навигация">
				<h2 class="not-found-page__panel-title">Куда перейти</h2>
				<ul class="not-found-page__links">
					<li><a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/')); ?>">Каталог товаров</a></li>
					<?php if ($show_news_link) : ?>
						<li><a href="<?php echo esc_url(home_url('/news/')); ?>">Новости</a></li>
					<?php endif; ?>
					<?php if ($show_recipes_link) : ?>
						<li><a href="<?php echo esc_url(home_url('/recipes/')); ?>">Рецепты</a></li>
					<?php endif; ?>
					<li><a href="<?php echo esc_url(home_url('/delivery/')); ?>">Доставка</a></li>
					<li><a href="<?php echo esc_url(home_url('/contacts/')); ?>">Контакты</a></li>
				</ul>
			</aside>
		</div>
	</div>
</section>

<?php
get_footer();
