<?php
defined('ABSPATH') || exit;

get_header();

$limit = 12;
$all_ids = is_user_logged_in() ? italika_favorites_get_product_ids(0) : [];
$filtered_ids = $all_ids;
$cards_html = is_user_logged_in() ? italika_favorites_render_cards($filtered_ids, 0, $limit) : '';
$rendered_count = min($limit, count($filtered_ids));
$has_more = $rendered_count < count($filtered_ids);
$filters_html = is_user_logged_in() ? italika_favorites_render_filters($all_ids, 0) : '';
$summary = is_user_logged_in()
	? italika_favorites_get_summary(count($all_ids), count($filtered_ids), 0)
	: 'Войдите в личный кабинет, чтобы видеть сохраненные товары.';
$catalog_url = italika_favorites_get_catalog_url();
$account_url = function_exists('italika_wc_get_account_url') ? italika_wc_get_account_url() : wp_login_url(italika_favorites_get_archive_url());
?>

<section
	class="favorites-page js-favorites-page"
	data-limit="<?php echo esc_attr((string) $limit); ?>"
	data-rendered-count="<?php echo esc_attr((string) $rendered_count); ?>"
	data-total-count="<?php echo esc_attr((string) count($filtered_ids)); ?>"
	data-active-category-id="0">
	<div class="container">
		<div class="favorites-page__head">
			<div class="favorites-page__intro">
				<span class="favorites-page__eyebrow">Избранное</span>
				<h1 class="favorites-page__title">Любимые товары</h1>
				<p class="favorites-page__summary js-favorites-page-summary"><?php echo esc_html($summary); ?></p>
			</div>
		</div>

		<?php if (is_user_logged_in()) : ?>
			<div class="favorites-page__filters js-favorites-page-filters" aria-label="Фильтр по категориям" <?php echo empty($all_ids) ? 'hidden' : ''; ?>>
				<?php echo $filters_html; ?>
			</div>

			<div class="sale-products__grid favorites-page__grid js-favorites-page-grid" <?php echo empty($all_ids) ? 'hidden' : ''; ?>>
				<?php echo $cards_html; ?>
			</div>

			<div class="sale-products__actions favorites-page__actions">
				<button class="sale-products__more js-favorites-page-more" type="button" <?php echo $has_more ? '' : 'hidden'; ?>>
					<span class="sale-products__more-text">Загрузить еще</span>
					<span class="sale-products__more-spinner" aria-hidden="true"></span>
				</button>
			</div>

			<div class="favorites-page__empty js-favorites-page-empty" <?php echo empty($all_ids) ? '' : 'hidden'; ?>>
				<span class="favorites-page__empty-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none">
						<path d="M12 20.2 4.7 13.4A4.9 4.9 0 0 1 11.5 6l.5.6.5-.6a4.9 4.9 0 0 1 6.8 7.3L12 20.2Z" stroke-width="1.8" stroke-linejoin="round"></path>
					</svg>
				</span>
				<h2 class="favorites-page__empty-title">В избранном пока пусто</h2>
				<p class="favorites-page__empty-text">Добавляйте товары сердцем в каталоге, акциях или результатах поиска.</p>
				<a class="favorites-page__empty-link" href="<?php echo esc_url($catalog_url); ?>">Перейти к товарам</a>
			</div>
		<?php else : ?>
			<div class="favorites-page__empty">
				<span class="favorites-page__empty-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none">
						<path d="M12 20.2 4.7 13.4A4.9 4.9 0 0 1 11.5 6l.5.6.5-.6a4.9 4.9 0 0 1 6.8 7.3L12 20.2Z" stroke-width="1.8" stroke-linejoin="round"></path>
					</svg>
				</span>
				<h2 class="favorites-page__empty-title">Войдите в кабинет</h2>
				<p class="favorites-page__empty-text">Избранное хранится в вашем аккаунте и синхронизируется между устройствами.</p>
				<a class="favorites-page__empty-link" href="<?php echo esc_url($account_url); ?>">Войти или зарегистрироваться</a>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
