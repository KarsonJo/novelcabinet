<?php

namespace KarsonJo\BookPost;

/**
 * 每次载入前调用
 * 提供页面相关的支持
 */

// ========== 自定义页面路由 ==========
// https://stackoverflow.com/questions/25310665/wordpress-how-to-create-a-rewrite-rule-for-a-file-in-a-custom-plugin

// ========== https://my.site/bookfinder ==========

function bookfinder_rewrite()
{
    add_rewrite_rule('^bookfinder/?$', 'index.php?kbp_book_finder=1', 'top');
}
add_action('init', 'KarsonJo\\BookPost\\bookfinder_rewrite');

function bookfinder_query_vars($vars)
{
    $vars[] = 'kbp_book_finder';
    return $vars;
}
add_filter('query_vars', 'KarsonJo\\BookPost\\bookfinder_query_vars');

function bookfinder_template($template)
{
    if (get_query_var('kbp_book_finder'))
        // return get_template_directory() . '/resources/views/book-finder.blade.php';
        // https://discourse.roots.io/t/load-a-specific-blade-template-for-custom-url-structure-with-wp-rewrite/22951
        return locate_template(app('sage.finder')->locate('book-finder'));

    return $template;
}
add_filter('template_include', 'KarsonJo\\BookPost\\bookfinder_template');

// 初次：刷新
add_action('after_switch_theme', 'flush_rewrite_rules');
