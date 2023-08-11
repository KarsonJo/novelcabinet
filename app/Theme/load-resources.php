<?php

namespace NovelCabinet;

use function NovelCabinet\Utility\enqueue_script_data;

/**
 * 任何无特定分类的、主题相关的资源加载都可以放在这里
 */

/**
 * 注册css和js
 * Register css and js
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('font-awesome-defer', 'https://site-assets.fontawesome.com/releases/v6.4.2/css/all.css', null, null);
    // wp_enqueue_script('font-awesome-defer', 'https://site-assets.fontawesome.com/releases/v6.4.2/js/all.js', null, null);
    // wp_enqueue_style('font-awesome-defer', 'https://site-assets.fontawesome.com/releases/v6.4.2/css/all.css', null, null);
    // wp_register_script('jquery', 'https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.min.js');
});

/**
 * 翻译
 */
add_action('after_setup_theme', function () {
    load_theme_textdomain('NovelCabinet', get_template_directory() . '/resources/lang');
});

/**
 * 主题需要传达给前端的相关的偏好设置
 */
add_action('wp_enqueue_scripts', function () {
    enqueue_script_data(
        'themeConfig',
        [
            'trailingSlash' => user_trailingslashit(''),
        ],
        'theme-novelcabinet'
    );
});

/**
 * 移除admin-bar
 */
// add_filter( 'show_admin_bar', '__return_false' );

/**
 * 移除global-styles-inline-css
 */
 add_action( 'init', function () {
    remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
    remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
 } );

 // 关闭 url guessing
add_filter( 'do_redirect_guess_404_permalink', '__return_false' );