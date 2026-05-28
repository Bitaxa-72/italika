<?php

defined('ABSPATH') || exit;

get_header();

if (function_exists('italika_posts_archive_render_page')) {
	italika_posts_archive_render_page('news');
}

get_footer();
