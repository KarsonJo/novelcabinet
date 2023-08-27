<?php

namespace KarsonJo\BookPost {

    use KarsonJo\BookPost\Route\QueryData;

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
                static::reconstructBookPermalink();
                if (static::KBP_TEMPLATE_SUPPORT === true)
                    static::addTemplateSupport();
            }, 1);
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
                'hierarchical'          => false,
                'has_archive'           => true,
                // 'rewrite'               => array('slug' => static::KBP_BOOK_SLUG, 'with_front' => false),
                'rewrite'               => false,
                'rewrite'               => true,
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
         * 重新构建book类型的permalink
         * 1. 明明不能设置成hierarchical，但是必须表现成hierarchical
         * 2. 因此引入%book_id%和%chapter_id%两层slug
         * 3. 为避免重复，不使用主查询，而提前实现页面跳转和404处理
         * @return void 
         */
        static function reconstructBookPermalink()
        {

            /**
             * 改变 permalink structure
             * 
             * https://wordpress.stackexchange.com/questions/21022/mixing-custom-post-type-and-taxonomy-rewrite-structures/22490#22490
             * https://stackoverflow.com/questions/23698827/custom-permalink-structure-custom-post-type-custom-taxonomy-post-name
             * https://wordpress.stackexchange.com/questions/203951/remove-slug-from-custom-post-type-post-urls
             * https://wordpress.stackexchange.com/questions/369343/put-post-id-on-the-custom-post-type-url
             * 
             * 
             * https://wordpress.stackexchange.com/questions/40353/change-custom-post-type-to-hierarchical-after-being-registered
             */
            global $wp_rewrite;
            $wp_rewrite->extra_permastructs[BookPost::KBP_BOOK]['struct'] = static::KBP_BOOK_SLUG . '/%book_id%/%chapter_id%';

            /**
             * 将permalink structure中的slugs加入rewrite tag
             * 将捕获值并变成对应名称query_var
             */
            add_rewrite_tag('%book_id%', '(\d+?)');
            add_rewrite_tag('%chapter_id%', '(\d*?)');


            /**
             * 不用add_rewrite_rule，而是直接检测并修改query_vars的情况
             * book_id和chapter_id是add_rewrite_tag时自动加入的查询键
             * ~~有优化空间，这里也可以短路主查询（已完成）~~
             * 这个函数将代替单本书的主查询
             * 
             * https://wordpress.stackexchange.com/questions/139834/obliterate-the-main-query-and-replace-it
             */
            add_filter('request', function (array $query_vars) {
                if (is_admin())
                    return $query_vars;

                $book_id = $query_vars['book_id'] ?? false;
                // 是书？
                if ($book_id) {
                    $book_post = get_post($book_id);

                    // 假书，溜了
                    if (!$book_post || $book_post->post_type != static::KBP_BOOK)
                        return ['p' => -1];

                    // 设置查询字符串
                    $query_vars['post_type'] = static::KBP_BOOK;
                    $chatper_id = $query_vars['chapter_id'] ?? false;

                    // 是章节？
                    if ($chatper_id) {
                        $ancestor = last(get_post_ancestors($chatper_id));
                        // 是这本书的章节？

                        if ($ancestor == $book_id)
                            $query_vars['p'] = $chatper_id;
                        else
                            return ['p' => -1];
                    } else {
                        // 是书的介绍，必须是根节点
                        if ($book_post->post_parent === 0)
                            $query_vars['p'] = $book_id;
                        else
                            return ['p' => -1];
                    }
                }

                return $query_vars;
            });

            /**
             * 短路主查询：
             * 因为在book设置了查询id的情况下，大概率是已经查过了，直接读缓存就完事
             * 如果没查，该函数直接操办它的仪式，因此任何情况下都不需要主查询
             */
            add_filter('posts_pre_query', function ($posts, $query) {
                if ($query->is_main_query() && !is_admin()) {
                    // 不是，WordPress会对404进行主查询，有病病？？
                    if ($query->is_404())
                        return [];
                    // 阻断对单个book_post的查询
                    else if ($query->get('post_type') === static::KBP_BOOK/* && $query->is_singular() */) {
                        $p = $query->get('p', false);
                        // 设置了id
                        if ($p != false && is_numeric($p)) {
                            $p = intval($p);
                            if (!$p)
                                return [get_post($p)];
                            // 但是无效（其实这个在上一步is_404已经排掉，保险起见吧）
                            else if ($p <= 0)
                                return [];
                        }
                    }
                }
                return $posts;
            }, 10, 2);


            /**
             * 手动替换permalink structure 的placeholders
             */
            add_filter('post_type_link', function ($post_link, $post) {
                if ($post && $post->post_type === BookPost::KBP_BOOK) {
                    $ancestor = last(get_post_ancestors($post->ID));
                    // is chapter
                    if ($ancestor) {
                        $post_link = str_replace('%book_id%', $ancestor, $post_link);
                        $post_link = str_replace('%chapter_id%', $post->ID, $post_link);
                    } else {
                        $post_link = str_replace('%book_id%', $post->ID, $post_link);
                        $post_link = str_replace('%chapter_id%', '', $post_link);
                    }
                    $post_link = preg_replace('#(?<!:)//#', '/', $post_link);
                }
                return $post_link;
            }, 10, 2);
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
