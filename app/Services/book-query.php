<?php

namespace KarsonJo\BookPost {

    use WP_Post;

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
     * 获取所有书类型
     * @return \WP_Term[]|false
     */
    function get_all_genres()
    {
        return get_terms([
            'taxonomy' => KBP_BOOK_GENRE,
            'hide_empty' => false,
        ]);
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
        return get_the_terms($id, KBP_BOOK_GENRE);
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
}

namespace KarsonJo\BookPost\SqlQuery {

    use Exception;
    use KarsonJo\BookPost\Book;
    use KarsonJo\Utilities\PostCache\CacheHelpers;
    use TenQuality\WP\Database\QueryBuilder;
    use WP_Post;
    use WP_User;

    define('KBP_CACHE_DOMAIN', 'kbp_post_cache');
    /**
     * 传入false时抛出$wpdb->last_error异常
     */
    function check_wpdb_result($result)
    {
        global $wpdb;
        if ($result === false)
            throw QueryException::wpdbException($wpdb->last_error);
    }

    /**
     * 获取用户的所有收藏夹
     * 如果给定文章ID，则还会返回收藏夹是否收藏该文章的情况
     * @param int[] $visibility 可见性集合，默认为全部
     * @return stdClass[]|false 字段：ID, list_title[, in_fav]
     */
    function get_user_favorite_lists(WP_User|int $user, array $visibility = null, WP_Post|int $post = 0): array|false
    {
        if ($post instanceof WP_Post) $post = $post->ID;
        if ($user instanceof WP_User) $user = $user->ID;
        if (!$user) return false;

        // 用户的所有收藏夹
        //select ID, list_title from wp_kbp_favorite_lists where user_id=1;
        // 
        /**
         * 用户所有收藏夹，并关联某post(9)的收藏情况 
         * select ID, list_title, CASE WHEN post_id IS NULL THEN 0 ELSE 1 END in_fav 
         * from wp_kbp_favorite_lists l 
         * left join wp_kbp_favorite_relationships r on l.ID = r.list_id and r.post_id = 9 
         * where user_id=1;
         */
        $res = QueryBuilder::create()->select('ID')->select('list_title')->from('kbp_favorite_lists l')->where(['user_id' => $user]);
        if ($visibility)
            $res->where([
                'visibility' => ['operator' => 'IN', 'value' => $visibility]
            ]);
        if ($post) {
            $res->join('kbp_favorite_relationships r', [
                ['key_a' => 'l.ID', 'key_b' => 'r.list_id'],
                ['key_a' => 'r.post_id', 'key_b' => $post],
            ], 'LEFT')
                ->select('CASE WHEN r.post_id IS NULL THEN 0 ELSE 1 END in_fav');
        }
        return $res->get();
    }

    /**
     * 获取用户所有包含当前文章的收藏夹
     * @return stdClass[]|false 字段：ID, list_title
     */
    function get_user_post_favorite(WP_Post|int $post, WP_User|int $user, array $visibility = null): array|false
    {
        if ($post instanceof WP_Post) $post = $post->ID;
        if ($user instanceof WP_User) $user = $user->ID;
        if (!$user || !$post) return false;

        $res = QueryBuilder::create()
            ->select('ID')->select('list_title')
            ->from('kbp_favorite_lists l')
            ->join('kbp_favorite_relationships r', [['key_a' => 'l.ID', 'key_b' => 'r.list_id']], 'INNER')
            ->where(['l.user_id' => $user, 'r.post_id' => $post]);

        if ($visibility)
            $res->where([
                'visibility' => ['operator' => 'IN', 'value' => $visibility]
            ]);

        return $res->get();
    }

    /**
     * 更新用户对某篇文章的收藏情况为给定列表
     * @param int[] $fav_list 这篇文章的所有收藏夹id
     */
    function update_user_post_favorite(WP_Post|int $post, WP_User|int $user, array $fav_lists_id): bool
    {
        if ($post instanceof WP_Post) $post = $post->ID;
        if ($user instanceof WP_User) $user = $user->ID;

        // 用户的所有收藏夹
        $all_fav_lists = get_user_favorite_lists($user, null, $post);
        $all_fav_lists_id = array_map(fn ($item) => $item->ID, $all_fav_lists);

        // 用户含有当前文章的收藏夹
        $post_fav_lists = array_filter($all_fav_lists, fn ($item) => $item->in_fav);
        $post_fav_lists_id = array_map(fn ($item) => $item->ID, $post_fav_lists);

        //限制在用户所拥有的的列表范围
        $fav_lists_id = array_intersect($fav_lists_id, $all_fav_lists_id);

        //计算差异集合
        $added = array_diff($fav_lists_id, $post_fav_lists_id);
        $removed = array_diff($post_fav_lists_id, $fav_lists_id);

        if (!$added && !$removed)
            return true;

        try {
            global $wpdb;
            $wpdb->query('START TRANSACTION');

            if ($added) {
                $table_fav = $wpdb->prefix . 'kbp_favorite_relationships';

                // https://stackoverflow.com/questions/12373903/wordpress-wpdb-insert-multiple-records
                $values = implode(',', array_map(fn ($list_id) => $wpdb->prepare('(%d,%d)', $list_id, $post), $added));
                // print_r("adding");
                // 插入现有的
                // insert into wp_kbp_favorite_relationships(post_id,list_id) values(1,1) on duplicate key update post_id=post_id;
                $result = $wpdb->query("
                INSERT  INTO $table_fav (list_id, post_id)
                VALUES  $values 
                ON DUPLICATE KEY
                UPDATE  list_id=list_id;");

                // print_r($result === false ? "false" : "true");

                check_wpdb_result($result);
            }
            // print_r("hello");
            if ($removed) {
                $table_fav_relationships = $wpdb->prefix . 'kbp_favorite_relationships';

                // print_r($removed);
                $values = implode(',', array_map(fn ($list_id) => $wpdb->prepare('%d', $list_id), $removed));
                // print_r("removing");
                // print_r($values);
                // print_r($wpdb->prepare("
                // DELETE  FROM $table_fav_relationships
                // WHERE   post_id = %d and list_id in ($values);", $post));

                $result = $wpdb->query($wpdb->prepare("
                DELETE  FROM $table_fav_relationships
                WHERE   post_id = %d and list_id in ($values);", $post));

                // print_r($result === false ? "false" : "true");

                check_wpdb_result($result);
            }

            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
        return true;
    }

    /**
     * 添加用户收藏夹
     * 用户不存在返回false，其它执行失败时抛出异常
     * @param int $visibility 列表的公开可见性 0: 私有 1: 公开，其它待定
     * @return int 新建收藏夹的ID，失败时抛出异常
     */
    function create_user_favorite_list(WP_User|int $user, string $title, int $visibility = 0): int
    {
        if ($user instanceof WP_User) $user = $user->ID;
        if (!$user) return false;

        //最大长度[todo]:变成option
        $len = strlen($title);
        if ($len > 20 || $len <= 0)
            throw QueryException::fieldOutOfRange();

        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'kbp_favorite_lists';
            $result = $wpdb->query($wpdb->prepare("
                insert into $table_name (user_id, list_title, visibility)
                values      (%d, %s, %d)", $user, $title, $visibility));

            check_wpdb_result($result);

            return $wpdb->get_var("SELECT LAST_INSERT_ID()");
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 获取某篇文章的收藏用户数
     * 不建议在循环中使用
     */
    function get_favorite_count(WP_Post|int $id)
    {
        if ($id instanceof WP_Post) $id = $id->ID;

        // 尝试从wp缓存中读取
        $key = "book-$id-fav-count";
        $callback = fn () => QueryBuilder::create()
            ->from('kbp_favorite_lists f')
            ->join('kbp_favorite_relationships r', [['key_a' => 'f.id', 'key_b' => 'r.list_id']], 'INNER')
            ->where(['r.post_id' => $id])
            ->count('distinct user_id');

        return CacheHelpers::getOrSetCache($key, KBP_CACHE_DOMAIN, $callback);
    }

    /**
     * 获取用户对某文章的评分
     */
    function get_book_user_rating(WP_Post|int $post, WP_User|int $user = 0): string|null
    {
        if ($post instanceof WP_Post) $post = $post->ID;

        if (!$user) $user = wp_get_current_user();
        if ($user instanceof WP_User) $user = $user->ID;
        if (!$user) return false;

        $key = "book-$post-user-$user-rating";
        $callback = fn () => QueryBuilder::create()
            ->select('rating')
            ->from('kbp_rating')
            ->where(['post_id' => $post, 'user_id' => $user])
            ->value();

        return CacheHelpers::getOrSetCache($key, KBP_CACHE_DOMAIN, $callback);
    }

    /**
     * 获取文章的评分
     * @return
     */
    function get_book_rating(WP_Post|int $post): string|null
    {
        if ($post instanceof WP_Post) $post = $post->ID;

        $key = "book-$post-rating";
        $callback = fn () => QueryBuilder::create()->select('rating_avg')->from('kbp_postmeta')->where(['post_id' => $post])->value();

        return CacheHelpers::getOrSetCache($key, KBP_CACHE_DOMAIN, $callback);
    }

    /**
     * 设置用户对文章的评分
     */
    function set_book_rating(WP_Post|int $post, WP_User|int $user, float $rating)
    {
        if ($post instanceof WP_Post) $post = $post->ID;
        if (is_int($user)) $user = get_user_by('id', $user);

        if (!$user)
            return false;

        if (!is_numeric($rating))
            return false;

        $role_weight = [
            'administrator' => 10000,
            'editor' => 3,
            'author' => 3,
            'contributor' => 1,
            'subscriber' => 1,
        ];

        $weight = $role_weight[$user->roles[0]] ?? 1;
        $rating = floatval($rating);
        $weighted_rating = $weight * $rating;
        // echo $rating;
        // return;

        // $record = QueryBuilder::create()
        // ->from('kbp_rating')
        // ->where([
        //     ['post_id' => $post],
        //     ['user_id' => $user->ID],
        // ])
        // ->count();

        // // 已经有记录了
        // if ($record > 0)
        //     return false;

        try {
            global $wpdb;
            $wpdb->query('START TRANSACTION');

            // 更新加权评分(10000, 9.6) + (1, 7)：UPDATE test SET weight = weight + 1, rating = (rating * weight + 7) / (weight + 1) WHERE id = 1; 

            // INSERT INTO test (id, weight, rating) VALUES (1, 1, 7)
            // ON DUPLICATE KEY UPDATE weight = weight + 1, rating = (rating * weight + 7) / (weight + 1);

            // 插入评分表
            $table_rating = $wpdb->prefix . 'kbp_rating';
            $result = $wpdb->query($wpdb->prepare("
            insert into $table_rating (post_id, user_id, rating, weight, time)
            values      (%d, %d, %f, %d, %s)", $post, $user->ID, $rating, $weight, gmdate('Y-m-d H:i:s')));

            check_wpdb_result($result);

            // 更新加权评分
            $table_posts = $wpdb->prefix . 'kbp_postmeta';
            $result = $wpdb->query($wpdb->prepare("
            insert into $table_posts (post_id, rating_weight, rating_avg)
            values      (%d, %d, %f)
            on duplicate key
            update      rating_avg = (rating_avg * rating_weight + %f) / (rating_weight + %d),
            rating_weight = rating_weight + %d;", $post, $weight, $rating, $weighted_rating, $weight, $weight));

            check_wpdb_result($result);

            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    

    function get_books_sql()
    {
        $builder = QueryBuilder::create();
        $builder->select('post_titles')
            ->from('posts as posts');

        return $builder;
    }
    function get_book_of_genres_sql(QueryBuilder $builder, array $genreIds): QueryBuilder
    {
        $builder->join('term_relationships', [
            [
                'key_a' => 'object_id',
                'key_b' => 'posts.ID',
            ]
        ])
            ->where([
                'term_taxonomy_id' => ['operator' => 'IN', 'value' => $genreIds],
            ]);
        return $builder;
    }

    /**
     * 测试用，不要用
     */
    function test_book_contents($book, $type)
    {

        // $query = \PluginEver\QueryBuilder\Query::init('query_users');

        $builder = QueryBuilder::create();
        $builder->select('p2.post_parent as parent2_id, p1.post_parent as parent_id, p1.ID, p1.post_title')
            ->from('posts p1')
            ->join('posts p2', [['key_a' => 'p1.post_parent', 'key_b' => 'p2.ID']], 'LEFT')
            ->where([
                'raw' => $book . ' in (p1.post_parent, p2.post_parent)',
                'p1.post_status' => 'publish',
                'p1.post_type' => $type,
            ])
            ->order_by('parent2_id')
            ->order_by('parent_id')
            ->order_by('p1.menu_order')
            ->order_by('p1.post_title');

        return $builder;

        // $results = $query->select('p2.post_parent as parent2_id, p1.post_parent as parent_id, p1.ID, p1.post_title')
        //     ->from('posts p1')
        //     ->leftJoin('posts p2', 'p2.ID', '=', 'p1.post_parent')
        //     ->whereIn('%d', ['p1.post_parent', 'p2.post_parent'])
        //     ->andWhere('p1.post_status', '=', 'publish')
        //     ->andWhere('p1.post_type', '=', '%s')
        //     ->order_by('parent2_id')
        //     ->order_by('parent_id')
        //     ->order_by('p1.menu_order')
        //     ->order_by('p1.post_title');

        // return $results;
    }

    // function param_test()
    // {
    //     $factory = new QueryFactory(new CommonEngine());
    //     $query = $factory
    //         ->select()
    //         ->from('users')
    //         ->where(field('name')->eq('1 OR 1=1'))
    //         ->andwhere(field('pass')->eq(3))
    //         ->compile();
    //     return $query;
    // }
}
