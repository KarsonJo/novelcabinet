<?php

namespace KarsonJo\BookRequest;

/**
 * 每次载入前调用
 * 提供页面相关的支持
 */
// ==========    Utility    ==========
const BOOKFINDER_ROUTER_PATH = 'bookfinder';
const BOOKFINDER_FLAG_NAME = 'kbp_book_finder';
class RouterVars
{
    public static $flags = [];
    private function __construct()
    {
    }
}

function is_book_finder()
{
    return !is_404() && array_key_exists(BOOKFINDER_FLAG_NAME, RouterVars::$flags);
}




// ========== 自定义页面路由 ==========
// ========== eg. https://my.site/bookfinder ==========

/**
 * 做法1，使用重写规则+查询字符串标记（未使用）
 * https://stackoverflow.com/questions/25310665/wordpress-how-to-create-a-rewrite-rule-for-a-file-in-a-custom-plugin
 * 
 * 缺点是会污染查询字符串
 */



/**
 * 做法2，使用正则匹配+变量标记
 * https://wordpress.stackexchange.com/questions/317760/how-do-i-know-if-a-rewritten-rule-was-applied
 * 
 */
function bookfinder_rewrite()
{
    //原地跳转，只为记录路径
    add_rewrite_rule('^' . BOOKFINDER_ROUTER_PATH . '$', 'index.php', 'top');
}
add_action('init', 'KarsonJo\\BookRequest\\bookfinder_rewrite');

function modify_query_vars($wp)
{
    // print_r($wp->request);
    if (preg_match('#^bookfinder/?.*?#', $wp->request))
        RouterVars::$flags[BOOKFINDER_FLAG_NAME] = true;
}
add_action('parse_request', 'KarsonJo\\BookRequest\\modify_query_vars'); // $wp->request设置后尽快调用


/**
 * 检测标记并选择性进行页面加载
 */
function bookfinder_template($template)
{
    if (is_book_finder()) {
        // return get_template_directory() . '/resources/views/book-finder.blade.php';
        // https://discourse.roots.io/t/load-a-specific-blade-template-for-custom-url-structure-with-wp-rewrite/22951
        return locate_template(app('sage.finder')->locate('book-finder'));
        // return locate_template('/resources/views/book-finder.blade.php');
    }
    return $template;
}
add_filter('template_include', 'KarsonJo\\BookRequest\\bookfinder_template');

/**
 * 下面俩函数 处理前缀斜杠重定向，与固定链接的格式一致
 * 通常是redirect_canonical()直接处理，但似乎对这个链接总是重定向至/结尾版本
 * 因此自己处理，算是workaround吧
 */
add_filter('redirect_canonical', function ($redirect) {
    return is_book_finder() ? false : $redirect;
}, 10, 2);
add_action('template_redirect', function () {
    if (is_book_finder()) {
        $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
        $uri_parts[0] = user_trailingslashit($uri_parts[0]);
        $uri = implode('?', $uri_parts);

        if ($_SERVER['REQUEST_URI'] != $uri) {
            wp_redirect($uri, 301);
            exit;
        }
    }
});




// 初次：刷新
add_action('after_switch_theme', 'flush_rewrite_rules');