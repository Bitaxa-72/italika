<?php

defined('ABSPATH') || exit;

if (!function_exists('italika_register_recipes_post_type')) {
	function italika_register_recipes_post_type() {
		$labels = [
			'name'                  => 'Рецепты',
			'singular_name'         => 'Рецепт',
			'menu_name'             => 'Рецепты',
			'name_admin_bar'        => 'Рецепт',
			'add_new'               => 'Добавить рецепт',
			'add_new_item'          => 'Добавить рецепт',
			'new_item'              => 'Новый рецепт',
			'edit_item'             => 'Редактировать рецепт',
			'view_item'             => 'Просмотреть рецепт',
			'all_items'             => 'Все рецепты',
			'search_items'          => 'Искать рецепты',
			'parent_item_colon'     => 'Родительский рецепт:',
			'not_found'             => 'Рецепты не найдены',
			'not_found_in_trash'    => 'В корзине рецептов не найдено',
			'archives'              => 'Архив рецептов',
			'attributes'            => 'Атрибуты рецепта',
			'insert_into_item'      => 'Вставить в рецепт',
			'uploaded_to_this_item' => 'Загружено для этого рецепта',
			'featured_image'        => 'Изображение рецепта',
			'set_featured_image'    => 'Установить изображение рецепта',
			'remove_featured_image' => 'Удалить изображение рецепта',
			'use_featured_image'    => 'Использовать как изображение рецепта',
			'filter_items_list'     => 'Фильтр списка рецептов',
			'items_list_navigation' => 'Навигация по списку рецептов',
			'items_list'            => 'Список рецептов',
			'item_published'        => 'Рецепт опубликован',
			'item_updated'          => 'Рецепт обновлён',
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'has_archive'         => true,
			'rewrite'             => [
				'slug' => 'recipes',
				'with_front' => false,
			],
			'menu_position'       => 6,
			'menu_icon'           => 'dashicons-carrot',
			'supports'            => [
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				'revisions',
			],
			'taxonomies'          => [
				'category',
				'post_tag',
			],
			'exclude_from_search' => false,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'can_export'          => true,
			'delete_with_user'    => false,
		];

		register_post_type('recipe', $args);
	}
	add_action('init', 'italika_register_recipes_post_type');
}