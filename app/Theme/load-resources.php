<?php

namespace NovelCabinet;

/**
 * Register css and js
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('font-awesome-defer', 'https://cdn.jsdelivr.net/gh/hung1001/font-awesome-pro-v6@44659d9/css/all.min.css');
    // wp_register_script('jquery', 'https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.min.js');
});
