<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});


//=======================

// Remove admin Toolbar
// add_filter( 'show_admin_bar', '__return_false' );

/**
 * remove global-styles-inline-css
 */

 add_action( 'init', function () {
    remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
    remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
 } );

 // disable url guessing
add_filter( 'do_redirect_guess_404_permalink', '__return_false' );
