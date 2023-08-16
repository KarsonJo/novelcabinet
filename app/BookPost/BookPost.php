<?php

namespace KarsonJo\BookPost {
    class BookPost
    {
        // should not modify after theme active
        const KBP_BOOK = 'book';
        const KBP_BOOK_GENRE = 'genre';
        // can modify
        const KBP_BOOK_SLUG = 'book';
        const KBP_BOOK_GENRE_SLUG = 'book-genre';
        const KBP_TEMPLATE_SUPPORT = false;
        public static function init()
        {
            add_action('init', function () {
                static::createBookTaxonomy();
                static::createBookPostType();
                if (static::KBP_TEMPLATE_SUPPORT === true)
                    static::addTemplateSupport();
            },1);
        }

        /**
         * 注册 "book" 自定义文章类型
         */
        static function createBookPostType()
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
                'rewrite'               => array('slug' => static::KBP_BOOK_SLUG, 'with_front' => false),
                'query_var'             => true,
                'capability_type'       => 'post',
                'show_in_menu'          => true,
                'show_ui'               => true,
                'show_in_nav_menus'     => true,
                'can_export'            => true,
            );

            register_post_type(static::KBP_BOOK, $args);
        }

        static function createBookTaxonomy()
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
                'rewrite'           => array('slug' => static::KBP_BOOK_GENRE_SLUG),
            );

            register_taxonomy(static::KBP_BOOK_GENRE, array(static::KBP_BOOK), $args);
        }

        /**
         * 为BookPostType增加名为bookintro.php和bookchapter.php的模板
         * @return void 
         */
        static function addTemplateSupport()
        {
            add_filter('template_include', function ($template) {
                global $post;

                if (get_post_type($post) === static::KBP_BOOK) {
                    $temp = empty(get_post_ancestors($post)) ? locate_template('bookintro.php') : locate_template('bookchapter.php');
                    $template = $temp ? $temp : $template;
                }

                return $template;
            });
        }
    }
}
