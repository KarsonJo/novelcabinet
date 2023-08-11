<?php

namespace KarsonJo\BookPost;

use DateTime;
use KarsonJo\BookPost\SqlQuery\BookFilterBuilder;
use PHP_CodeSniffer\Reports\Full;
use WP;

// should not modify after theme active
define('KBP_BOOK', 'book');
define('KBP_BOOK_GENRE', 'genre');

// can modify
define('KBP_BOOK_SLUG', 'book');
define('KBP_BOOK_GENRE_SLUG', 'book-genre');

define('KBP_TEMPLATE_SUPPORT', false);


add_action('init', 'KarsonJo\\BookPost\\wpdocs_create_book_taxonomies', 0);
add_action('init', 'KarsonJo\\BookPost\\custom_post_type_book');
if (KBP_TEMPLATE_SUPPORT === true)
    add_filter('template_include', 'KarsonJo\\BookPost\\book_template_include');

function book_template_include($template)
{
    global $post;

    if (get_post_type($post) === KBP_BOOK) {
        $temp = empty(get_post_ancestors($post)) ? locate_template('bookintro.php') : locate_template('bookchapter.php');
        $template = $temp ? $temp : $template;
    }

    return $template;
}

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
        'rewrite'               => array('slug' => KBP_BOOK_SLUG, 'with_front' => false),
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
        'query_var'         => false,
        'rewrite'           => array('slug' => KBP_BOOK_GENRE_SLUG),
    );

    register_taxonomy(KBP_BOOK_GENRE, array(KBP_BOOK), $args);
}

use WP_Post;

class Book
{
    public int $ID;
    /**
     * 书的封面图url
     */
    public string $cover;
    /**
     * 书的永久链接
     */
    public string $permalink;

    public string $title;
    public string $author;
    public string $excerpt;
    public ?DateTime $updateTime;
    /**
     * Book类型的Genre Taxonomy
     */
    public array $genres;

    public float $rating;
    /**
     * 评分权重，可用作记录评分人数等
     */
    public int $ratingWeight;
    public int $wordCount;

    /**
     * 书的目录
     * 懒加载变量 $this->contents;
     */
    private ?BookContents $_contents = null;

    // /**
    //  * 只用ID初始化的WP_Post对象
    //  * 用于某些只需要id，但需要传入WP_Post的WordPress函数
    //  * （避免直接传入id多查询一次数据库） <--木大哒
    //  */
    // protected WP_Post $_post;

    protected function __construct()
    {
    }

    public static function initBookFromPost(WP_Post|int $id): ?Book
    {
        if ($id instanceof WP_Post)
            $id = $id->ID;
        $book = BookFilterBuilder::create(null, false)->of_id($id)->get_as_book();
        if (!$book)
            return null;

        return $book[0];
    }

    public static function initBookFromArray(array $params): ?Book
    {
        if (!array_key_exists('ID', $params))
            return null;

        $book = new Book();

        // $_post = new WP_Post((object)['ID' => $params['ID']]);
        // $_post->filter = 'raw'; // 为了逃避查询
        // $_post->post_type = KBP_BOOK;
        $book->ID = $params['ID'];
        // $book->_post = $_post;
        // print_r($params);

        $book->title = $params['post_title'] ?? '';
        $book->excerpt = $params['post_excerpt'] ?? '';
        $book->updateTime = isset($params['update_date']) ? DateTime::createFromFormat('Y-m-d G:i:s', $params['update_date']) : null;
        $book->rating = round($params['rating'], 2) ?? 0;
        $book->ratingWeight = $params['rating_weight'] ?? 0;
        $book->wordCount = $params['word_count'] ?? 0;

        if (isset($params['post_author']))
            $book->author = get_the_author_meta('display_name', $params['post_author']);

        // 获取额外信息
        // $book->permalink = get_permalink($_post);
        $book->permalink = get_post_permalink($book->ID);

        $images = wp_get_attachment_image_src(get_post_thumbnail_id($book->ID), "full");
        $book->cover = is_array($images) ? $images[0] : "";


        $book->genres = get_the_terms($book->ID, $bookGenre ?? defined('KBP_BOOK_GENRE') ? KBP_BOOK_GENRE : "category");

        return $book;
    }

    public static function initBookFromParams(...$params): ?Book
    {
        return Book::initBookFromArray($params);
    }

    public static function initBookFromObject(object $obj): ?Book
    {
        return Book::initBookFromArray((array)$obj);
    }

    public function __get($name)
    {
        // Check if the property is not already loaded
        if ($name === 'contents') {
            if ($this->ID === null)
                return null;

            if ($this->_contents == null) {
                $this->_contents = new BookContents($this->ID);
            }
            return $this->_contents;
        }
        return null;
    }

    // public static function initBookFromAnonymous(object $obj): ?Book
    // {
    //     if ($obj || !isset($obj->ID))
    //         return null;

    //     $book = new Book();

    //     foreach (get_object_vars($obj) as $key => $value) {
    //         $book->$key = $value;
    //     }



    //     if ($obj->ID)
    //         $book = new Book();
    //     $_post = new WP_Post((object)['ID' => $ID]);

    //     $book->ID = $ID;
    //     $book->_post = $_post;
    // }

    // protected function loadAssociatedData()
    // {
    //     $images = wp_get_attachment_image_src(get_post_thumbnail_id($this->_post), "full");
    //     $this->cover = is_array($images) ? $images[0] : "";
    //     $this->permalink = get_permalink($this->_post);
    //     $this->tags = get_the_terms($this->_post, $bookGenre ?? defined('KBP_BOOK_GENRE') ? KBP_BOOK_GENRE : "category");
    // }
}
