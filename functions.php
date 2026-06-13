<?php
if (!defined('ABSPATH')) exit;
add_action('after_setup_theme', function(){add_theme_support('post-thumbnails');add_theme_support('title-tag');add_theme_support('html5',['search-form','gallery','caption']);});
add_action('wp_enqueue_scripts', function(){wp_enqueue_style('tager-style',get_stylesheet_uri(),[], '12.0.0');});
