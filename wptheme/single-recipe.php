<?php

defined('ABSPATH') || exit;

get_header();

if (function_exists('italika_posts_single_render')) {
	italika_posts_single_render('recipes');
}

get_footer();
