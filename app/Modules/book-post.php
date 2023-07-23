<?php

namespace KarsonJo\BookPost;

use PHP_CodeSniffer\Reports\Full;
use WP_Post;

define('KBP_BOOK', 'book');
define('KBP_BOOK_GENRE', 'genre');

define('KBP_TEMPLATE_SUPPORT', false);


add_action('init', 'KarsonJo\\BookPost\\wpdocs_create_book_taxonomies', 0);
add_action('init', 'KarsonJo\\BookPost\\custom_post_type_book');
if (KBP_TEMPLATE_SUPPORT === true)
    add_filter('template_include', 'KarsonJo\\BookPost\\book_template_include');


/**
 * 注册 "book" 自定义文章类型
 */
function custom_post_type_book()
{

    $labels = array(
        'name'                  => '图书',
        'singular_name'         => '图书',
        'add_new'               => '添加图书',
        'add_new_item'          => '添加新图书',
        'edit_item'             => '编辑图书',
        'new_item'              => '新图书',
        'all_items'             => '所有图书',
        'view_item'             => '查看图书',
        'search_items'          => '搜索图书',
        'not_found'             => '未找到任何图书',
        'not_found_in_trash'    => '回收站中没有图书',
        'menu_name'             => '图书'
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'show_in_rest'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-book',
        'supports'              => array('title', 'editor', 'thumbnail', 'page-attributes'),
        'taxonomies'            => array(),
        'hierarchical'          => true,
        'has_archive'           => true,
        'rewrite'               => array('slug' => KBP_BOOK, 'with_front' => false),
        'query_var'             => true,
        'capability_type'       => 'post',
        'show_in_menu'          => true,
        'show_ui'               => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
    );

    register_post_type(KBP_BOOK, $args);
    // write_log(get_post_types(array('public' => true, '_builtin' => false)));
}

function wpdocs_create_book_taxonomies()
{
    // Add new taxonomy, make it hierarchical (like categories)
    $labels = array(
        'name'              => _x('Genres', 'taxonomy general name', 'textdomain'),
        'singular_name'     => _x('Genre', 'taxonomy singular name', 'textdomain'),
        'search_items'      => __('Search Genres', 'textdomain'),
        'all_items'         => __('All Genres', 'textdomain'),
        'parent_item'       => __('Parent Genre', 'textdomain'),
        'parent_item_colon' => __('Parent Genre:', 'textdomain'),
        'edit_item'         => __('Edit Genre', 'textdomain'),
        'update_item'       => __('Update Genre', 'textdomain'),
        'add_new_item'      => __('Add New Genre', 'textdomain'),
        'new_item_name'     => __('New Genre Name', 'textdomain'),
        'menu_name'         => __('Genre', 'textdomain'),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => KBP_BOOK_GENRE),
    );

    register_taxonomy(KBP_BOOK_GENRE, array(KBP_BOOK), $args);
}

//==========功能==========
/**
 * 获取给定小说的所有卷与章节的元数据，常用于生成目录
 * 卷与章节均为WP_Post类型
 * [todo]: 目前会一同返回所有文章内容，考虑改为$wpdb直接查元数据
 * @param int $id 书的Id
 * @return \WP_Post[][] | false 按卷、章分的二维数组，数组第一个元素是卷元素
 */
function get_book_volume_chapters($id)
{
    $args = [
        'child_of' => $id,
        'post_type' => KBP_BOOK,
        'sort_order' => 'ASC',
        'sort_column' => 'menu_order,post_title',
    ];

    $pages = get_pages($args);
    if (!$pages) return false;



    // 按卷、章组织二维数组返回
    $res = [];
    $cnt = -1;
    $parent_id = -1;
    foreach ($pages as $page) {
        if ($page->post_parent != $parent_id) {
            $res[++$cnt] = []; //加新卷
            $parent_id = $page->ID;
        }

        $res[$cnt][] = $page;
    }

    return $res;
}

/**
 * 获取给定小说的所有卷与章节的元数据，常用于生成目录
 * @param WP_Post|int $book 书本身或从属的任何级别文章
 */
function get_book_contents(WP_Post|int $book): ?BookContents
{
    return new BookContents($book);
}

/**
 * [DEBUG]获取所有书籍
 * @return \WP_Post[]|false
 */
function get_all_books()
{

    $args = [
        'post_parent' => 0, // 获取所有没有父级的页面
        'post_type' => KBP_BOOK, // 获取所有页面
        // 'post_status' => 'publish', // 获取所有已发布的页面
    ];

    return get_posts($args);
}

/**
 * 获取书籍
 * @param array|int $param WordPress查询args，或者代表查询个数的int
 * @param int $paged 页码
 * @return \WP_Post[]|false
 */
function get_books($param = 9, $paged = 1)
{
    $args = is_array($param) ? $param : array(
        'numberposts' => is_numeric($param) ? $param : 9,
    );

    $args['post_type'] = KBP_BOOK;
    $args['paged'] = $paged;
    $args['post_parent'] = 0;


    return get_posts($args);
}

/**
 * 查找根文章
 * 用于从卷或章（子文章）找到所属书籍
 * @param \WP_Post|int $post 卷或章的对象或编号
 * @return \WP_Post|null 返回当前post的最根文章
 */
function get_book_from_post($post)
{
    $post = get_post($post);
    while ($post && $post->post_parent != 0)
        $post = get_post($post->post_parent);

    return $post;
}

/**
 * 获取书籍的分类信息
 * @param int $id 书本id
 * @return \WP_Term[]|false|\WP_Error 分类
 */
function get_book_genres($id)
{
    return get_the_terms($id, defined('KBP_BOOK_GENRE') ? KBP_BOOK_GENRE : "category");
}

/**
 * 获取书籍的封（第一张freature image）
 * @param int $id 书本id
 * @param string $size 图片大小，同get_post_thumbnail_id
 */
function get_book_cover($id, $size = 'full')
{
    $images = wp_get_attachment_image_src(get_post_thumbnail_id($id), $size);
    return is_array($images) ? $images[0] : "";
}

function book_template_include($template)
{
    global $post;

    if (get_post_type($post) === KBP_BOOK) {
        $temp = empty(get_post_ancestors($post)) ? locate_template('bookintro.php') : locate_template('bookchapter.php');
        $template = $temp ? $temp : $template;
    }

    return $template;
}
