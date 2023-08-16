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
            'post_type' => BookPost::KBP_BOOK,
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
            'post_type' => BookPost::KBP_BOOK, // 获取所有页面
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
            'taxonomy' => BookPost::KBP_BOOK_GENRE,
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

        $args['post_type'] = BookPost::KBP_BOOK;
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
        return get_the_terms($id, BookPost::KBP_BOOK_GENRE);
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

    use App\View\Composers\BookFinder;
    use Exception;
    use KarsonJo\BookPost\Book;
    use KarsonJo\Utilities\PostCache\CacheBuilder;
    use KarsonJo\BookPost\SqlQuery\QueryException;
    use KarsonJo\Utilities\PostCache\CacheHelpers;
    use TenQuality\WP\Database\QueryBuilder;
    use WP_Post;
    use WP_User;


    // use function KarsonJo\Utilities\PostCache\get_or_set_cache;

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



    // class BookFilterBuilder extends QueryBuilder
    // {
    //     protected QueryBuilder $bookFilter;
    //     public function __construct($id = null)
    //     {
    //         parent::__construct($id);
    //         // $this->select('post_title')
    //         $this->from('posts posts')
    //             ->where([
    //                 'posts.post_type' => KBP_BOOK,
    //                 'posts.post_parent' => 0,
    //             ]);
    //     }

    //     /**
    //      * 创建一个查询构造器。
    //      * @param string|null $id 构造器的id
    //      * @param bool $published 是否默认选择已发布文章
    //      */
    //     public static function create($id = null, bool $published = true): BookFilterBuilder
    //     {
    //         $builder = new self($id);
    //         if ($published)
    //             $builder->published();
    //         return $builder;
    //     }

    //     public function join($table, $args, $type = false, $add_prefix = true): BookFilterBuilder
    //     {
    //         global $wpdb;

    //         $repeated = in_array(($add_prefix ? $wpdb->prefix : '') . $table, array_column($this->builder['join'], 'table'));

    //         if ($repeated)
    //             return $this;

    //         parent::join($table, $args, $type, $add_prefix);
    //         return $this;
    //     }

    //     public function get($output = OBJECT, $callable_mapping = null, $calc_rows = false)
    //     {
    //         if (array_key_exists('page', $this->builder) && $this->builder['page'] > 0)
    //             $this->builder['offset'] += ($this->builder['page'] - 1) * $this->builder['limit'];
    //         return parent::get($output, $callable_mapping, $calc_rows);
    //     }

    //     /**
    //      * 页码，需要结合limit使用
    //      * 
    //      * [warning]目前只在get()查询中生效
    //      * @param int $page 页码，从1数起
    //      */
    //     public function page(int $page): BookFilterBuilder
    //     {
    //         $this->builder['page'] = $page;
    //         return $this;
    //     }

    //     //==================================================
    //     // 预制查询选项

    //     /**
    //      * 筛选已发布的书籍
    //      */
    //     public function published(): BookFilterBuilder
    //     {
    //         $this->where([
    //             'post_status' => 'publish',
    //         ]);
    //         return $this;
    //     }

    //     public function of_id(int|WP_Post $id): BookFilterBuilder
    //     {
    //         if ($id instanceof WP_Post)
    //             $id = $id->ID;
    //         $this->where(['posts.ID' => $id]);
    //         return $this;
    //     }

    //     public function of_author(int|WP_User $author): BookFilterBuilder
    //     {
    //         if ($author instanceof WP_User)
    //             $author = $author->ID;
    //         $this->where(['posts.post_author' => $author]);
    //         return $this;
    //     }

    //     /**
    //      * 筛选含有至少一个指定标签的书籍
    //      * @param int|int[] $genreIds taxonomy类型：genre
    //      */
    //     public function of_any_genres(int|array $genreIds): BookFilterBuilder
    //     {
    //         if (is_int($genreIds))
    //             $genreIds = [$genreIds];

    //         $this->join('term_relationships', [
    //             [
    //                 'key_a' => 'object_id',
    //                 'key_b' => 'posts.ID',
    //             ]
    //         ])
    //             ->where([
    //                 'term_taxonomy_id' => ['operator' => 'IN', 'value' => $genreIds],
    //             ]);
    //         return $this;
    //     }

    //     /**
    //      * 筛选含有所有给定标签的书籍
    //      * @param int|int[] $genreIds taxonomy类型：genre

    //      */
    //     public function of_all_genres(int|array $genreIds): BookFilterBuilder
    //     {
    //         // 转换字符串数字、剔除其它无效元素、去重
    //         $sanitized = [];
    //         foreach ($genreIds as $genreId) {
    //             if (is_string($genreId)) {
    //                 $genreId = intval($genreId);
    //                 if ($genreId == 0)
    //                     continue;
    //             }
    //             if (is_int($genreId))
    //                 $sanitized[$genreId] = $genreId;
    //         }


    //         $str = implode(',', $sanitized);
    //         $raw = "SELECT NULL
    //         FROM wp_posts __p
    //         JOIN wp_term_relationships __t ON __p.ID = __t.object_id
    //         WHERE __t.term_taxonomy_id IN ($str)
    //         AND posts.ID = __p.id
    //         GROUP BY __p.ID
    //         HAVING COUNT(1) = " . count($sanitized);


    //         // $raw = "SELECT tg.post_id
    //         // FROM TAGGINGS tg
    //         // JOIN TAGS t ON t.id = tg.tag_id
    //         // WHERE t.name IN ($str)
    //         // GROUP BY tg.post_id
    //         // HAVING COUNT(DISTINCT t.name) = " . count($genreIds);





    //         $this->where([
    //             'raw' => "EXISTS ($raw)",
    //         ]);
    //         return $this;
    //     }

    //     /**
    //      * 指定查询某段时间内更新的书籍
    //      * 参数范围详见函数实现，超出范围视为无效
    //      * @param int $index 选项的索引值
    //      */
    //     public function of_latest(int $index): BookFilterBuilder
    //     {
    //         $date = current_datetime()->format('Y-m-d');
    //         $now = current_datetime()->format('Y-m-d H:i:s');
    //         $query = "post_date between '$date' - interval %s and '$now'";
    //         switch ($index) {
    //             case 1:
    //                 $query = sprintf($query, '3 day');
    //                 break;
    //             case 2:
    //                 $query = sprintf($query, '7 day');
    //                 break;
    //             case 3:
    //                 $query = sprintf($query, '15 day');
    //                 break;
    //             case 4:
    //                 $query = sprintf($query, '1 month');
    //                 break;
    //             case 5:
    //                 $query = sprintf($query, '3 month');
    //                 break;
    //             default:
    //                 return $this;
    //         }
    //         $this->where([
    //             'raw' => $query,
    //         ]);
    //         return $this;
    //     }

    //     /**
    //      * 按时间排序
    //      */
    //     public function order_by_time(bool $ASC = false): BookFilterBuilder
    //     {
    //         $this->order_by('post_date', $ASC ? 'ASC' : 'DESC');
    //         return $this;
    //     }

    //     /**
    //      * 按评分排序
    //      */
    //     public function order_by_rating(bool $ASC = false): BookFilterBuilder
    //     {
    //         // print_r(123);
    //         // print_r(array_search('wp_kbp_postmeta mt', array_column($this->builder['join'], 'table')));
    //         // print_r(123);
    //         $this->join('kbp_postmeta mt', [['key_a' => 'mt.post_id', 'key_b' => 'posts.ID']], 'LEFT')
    //             ->order_by('mt.rating_avg', $ASC ? 'ASC' : 'DESC');
    //         // print_r($this->builder['join']);
    //         return $this;
    //     }

    //     /**
    //      * 在给定用户的收藏夹中
    //      * @param int $user 用户，默认为当前用户
    //      */
    //     public function in_favourite(int $user = 0)
    //     {
    //         /** 
    //          * select post_title, list_title from wp_posts posts 
    //          * inner join wp_kbp_favorite_relationships fav_r on posts.ID = fav_r.post_id 
    //          * innor join wp_kbp_favorite_lists fav_l on fav_r.list_id = fav_l.ID
    //          * where fav_l.user_id = 1
    //          * */

    //         $this->join('kbp_favorite_relationships fav_r', [['key_a' => 'posts.ID', 'key_b' => 'fav_r.post_id']], 'INNER')
    //             ->join('kbp_favorite_lists fav_l', [['key_a' => 'fav_r.list_id', 'key_b' => 'fav_l.ID']], 'INNER')
    //             ->where(['fav_l.user_id' => $user == 0 ? get_current_user_id() : $user]);
    //         return $this;
    //     }

    //     //==================================================
    //     // 获取结果

    //     /**
    //      * @return WP_Post[] 以WP_Post形式返回查询结果
    //      */
    //     public function get_as_post(): array
    //     {
    //         $this->builder['select'] = []; //clear select
    //         $result = $this->select('posts.*')->get();

    //         return array_map(fn ($record) => new WP_Post($record), $result);
    //     }

    //     /**
    //      * 以Book形式返回查询结果
    //      * 由于部分变量由WordPress内置函数给出，为了避免(N+1)查询，该函数提供统一预缓存
    //      * @param bool $with_meta 并统一预先查出post的meta信息
    //      * @param bool $with_thumbnail 并统一预先查出封面图(thumbnail post)及其meta信息
    //      * @param bool $with_book_taxonomy 并统一预先查出book类型的分类法
    //      * @return Book[]
    //      */
    //     public function get_as_book(bool $with_meta = true, $with_thumbnail = true, $with_book_taxonomy = true): array
    //     {
    //         $this->builder['select'] = []; //clear select
    //         $result = $this->select('distinct posts.ID')
    //             ->join('kbp_postmeta mt', [['key_a' => 'mt.post_id', 'key_b' => 'posts.ID']], 'LEFT')
    //             ->select('posts.post_author')
    //             ->select('posts.post_title')
    //             ->select('posts.post_excerpt')
    //             ->select('posts.post_modified update_date')
    //             ->select('mt.rating_weight')
    //             ->select('mt.rating_avg rating')
    //             ->select('mt.word_count')
    //             ->get();

    //         // if ($with_meta || $with_thumbnail || $with_book_taxonomy)
    //         $post_ids = array_map(fn ($record) => $record->ID, $result);

    //         $cacher = CacheBuilder::create();

    //         $cacher->cachePosts($post_ids);
    //         if ($with_meta)
    //             $cacher->cachePostmeta($post_ids);
    //         if ($with_thumbnail)
    //             $cacher->cacheThumbnailStatus($post_ids);
    //         if ($with_book_taxonomy)
    //             $cacher->cacheTaxonomy($post_ids);

    //         $cacher->cache();

    //         // $this->cache_posts($post_ids);

    //         // if ($with_meta)
    //         //     $this->cache_postmeta($post_ids);

    //         // if ($with_thumbnail)
    //         //     $this->cache_thumbnail_status($post_ids);

    //         // if ($with_book_taxonomy)
    //         //     $this->cache_taxonomy($post_ids);

    //         // $this->build_cache();

    //         return array_map(fn ($record) => Book::initBookFromObject($record), $result);
    //         // return [];
    //     }

    //     //==================================================
    //     // 简单的预缓存支持

    //     // protected $cached;

    //     // protected function prepare_cache(string $key, array &$value)
    //     // {
    //     //     // if ($key == 'posts') {
    //     //     //     print_r('[prepare_cache]');
    //     //     //     print_r("[$key]");
    //     //     //     print_r($value);
    //     //     //     print_r(isset($this->cached[$key]) ? $this->cached[$key] : "[empty]");
    //     //     // }

    //     //     if (!isset($this->cached[$key]))
    //     //         $this->cached[$key] = [];

    //     //     foreach ($value as $var)
    //     //         if (!isset($this->cached[$key][$var]))
    //     //             $this->cached[$key][$var] = $var;

    //     //     // if ($key == 'posts') {
    //     //     //     print_r(isset($this->cached[$key]) ? $this->cached[$key] : "[empty]");
    //     //     // }
    //     // }

    //     // protected function cache_posts(array &$post_ids)
    //     // {
    //     //     // get_posts([
    //     //     //     'include' => $post_ids,
    //     //     //     // 'post_type' => 'attachment',
    //     //     // ]);
    //     //     // $this->cached['posts'] += $post_ids;
    //     //     $this->prepare_cache('posts', $post_ids);
    //     // }

    //     // /**
    //     //  * 统一缓存所有给定文章的postmeta
    //     //  * https://hitchhackerguide.com/2011/11/01/reducing-postmeta-queries-with-update_meta_cache/
    //     //  * @param int[] &$post_ids
    //     //  */
    //     // protected function cache_postmeta(array &$post_ids)
    //     // {
    //     //     // update_meta_cache('post', $post_ids);
    //     //     $this->prepare_cache('postmeta', $post_ids);
    //     // }

    //     // /**
    //     //  * 统一缓存给定文章的特色图片状态
    //     //  * thumbnail post + postmeta
    //     //  * @param int[] &$post_ids
    //     //  */
    //     // protected function cache_thumbnail_status(array &$post_ids)
    //     // {
    //     //     // //必须先查metadata
    //     //     // $this->cache_postmeta($post_ids);
    //     //     // // 获取所有特色图片的id
    //     //     // $thumb_ids = array_map(function ($post_id) {
    //     //     //     return (int) get_post_meta($post_id, '_thumbnail_id', true);
    //     //     // }, $post_ids);
    //     //     // // 缓存所有特色图片所对应的文章（统一查询一遍）
    //     //     // get_posts([
    //     //     //     'include' => $thumb_ids,
    //     //     //     'post_type' => 'attachment',
    //     //     // ]);
    //     //     // // 缓存所有的postmeta                
    //     //     // $this->cache_postmeta($thumb_ids);

    //     //     // $this->cached['postmeta'] += $post_ids;
    //     //     $this->prepare_cache('thumbnail', $post_ids);
    //     // }

    //     // /**

    //     //  * 统一所有给定文章作为[object_type]类型的“所有”taxonomy
    //     //  * @param int[] &$post_ids
    //     //  */
    //     // protected function cache_taxonomy(array &$post_ids)
    //     // {
    //     //     // update_object_term_cache($post_ids, $object_type);
    //     //     $this->prepare_cache('taxonomy', $post_ids);
    //     // }

    //     // protected function build_cache()
    //     // {
    //     //     // print_r('[build_cache]');
    //     //     // postmeta
    //     //     if (isset($this->cached['postmeta']) || isset($this->cached['thumbnail'])) {
    //     //         $posts = $this->cached['postmeta'] + $this->cached['thumbnail'];
    //     //         update_meta_cache('post', $posts);
    //     //         // print_r('[build_cache]');
    //     //     }

    //     //     if (isset($this->cached['thumbnail'])) {
    //     //         // 需要获取postmeta
    //     //         $thumb_ids = array_map(function ($post_id) {
    //     //             return (int) get_post_meta($post_id, '_thumbnail_id', true);
    //     //         }, $this->cached['thumbnail']);

    //     //         // 缓存post
    //     //         // $this->cached['posts'] += $thumb_ids;
    //     //         $this->prepare_cache('posts', $thumb_ids);
    //     //         // print_r($this->cached['posts']);
    //     //         // 再次缓存postmeta
    //     //         update_meta_cache('post', $thumb_ids);
    //     //     }

    //     //     if (isset($this->cached['posts']))
    //     //         // $this->cache_posts($cached['posts']);
    //     //         get_posts([
    //     //             'include' => $this->cached['posts'],
    //     //             'post_type' => 'any',
    //     //             'post_status' => 'any',
    //     //         ]);

    //     //     if (isset($this->cached['taxonomy']))
    //     //         update_object_term_cache($this->cached['taxonomy'], KBP_BOOK);
    //     // }


    //     //==================================================

    //     /**
    //      * [DEBUG] 获取组装后的查询语句
    //      */
    //     public function debug_get_sql(): string
    //     {
    //         $call = function (string $name, string &$query) {
    //             $reflector = new \ReflectionObject($this);
    //             $method = $reflector->getMethod($name);
    //             $method->setAccessible(true);
    //             echo $method->invokeArgs($this, [&$query]);
    //         };

    //         $query = '';
    //         $call('_query_select', $query);
    //         $call('_query_from', $query);
    //         $call('_query_join', $query);
    //         $call('_query_where', $query);
    //         $call('_query_group', $query);
    //         $call('_query_having', $query);
    //         $call('_query_order', $query);
    //         $call('_query_limit', $query);
    //         $call('_query_offset', $query);

    //         return $query;
    //     }
    // }

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
