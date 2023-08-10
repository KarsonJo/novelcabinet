<?php

namespace NovelCabinet;

/**
 * Register css and js
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('font-awesome-defer', 'https://site-assets.fontawesome.com/releases/v6.4.2/css/all.css', null, null);
    // wp_enqueue_script('font-awesome-defer', 'https://site-assets.fontawesome.com/releases/v6.4.2/js/all.js', null, null);
    // wp_enqueue_style('font-awesome-defer', 'https://site-assets.fontawesome.com/releases/v6.4.2/css/all.css', null, null);
    // wp_register_script('jquery', 'https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.min.js');
});

add_action('after_setup_theme', function () {
    load_theme_textdomain('NovelCabinet', get_template_directory() . '/resources/lang');
});