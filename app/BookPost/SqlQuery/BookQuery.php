<?php

namespace KarsonJo\BookPost\SqlQuery {

    use Exception;
    use KarsonJo\BookPost\BookPost;
    use KarsonJo\Utilities\PostCache\CacheHelpers;
    use PHP_CodeSniffer\Standards\Squiz\Sniffs\Scope\StaticThisUsageSniff;
    use TenQuality\WP\Database\QueryBuilder;
    use WP_Post;
    use WP_Query;
    use WP_Term;
    use WP_User;

    use function KarsonJo\BookPost\book_database_init;

    class BookQuery
    {
        const KBP_CACHE_DOMAIN = 'kbp_post_cache';


        public static function firstTimeInit()
        {
            add_action("after_switch_theme", function () {
                // wp_kbp_postmeta
                $sql = "CREATE TABLE %s (
                    post_id bigint(20) unsigned NOT NULL,
                    rating_weight int(11) unsigned DEFAULT 0 NOT NULL,
                    rating_avg double DEFAULT 0 NOT NULL,
                    word_count int(11) unsigned DEFAULT 0 NOT NULL,
                    PRIMARY KEY  (post_id),
                    KEY idx_id_rating_word (post_id, rating_avg, word_count),
                    KEY idx_rating_avg (rating_avg)
                ) %s;";
                static::createDBTable('kbp_postmeta', $sql);

                // wp_kbp_rating
                $sql = "CREATE TABLE %s (
                    post_id bigint(20) unsigned NOT NULL,
                    user_id bigint(20) unsigned NOT NULL,
                    weight int(11) NOT NULL,
                    rating float NOT NULL,
                    time datetime DEFAULT '1000-01-01 00:00:00' NOT NULL,
                    PRIMARY KEY  (user_id, post_id),
                    KEY idx_time (time)
                ) %s;";
                static::createDBTable('kbp_rating', $sql);

                // wp_kbp_favorite_lists
                $sql = "CREATE TABLE %s (
                    ID int(11) unsigned NOT NULL AUTO_INCREMENT,
                    user_id bigint(20) unsigned NOT NULL,
                    list_title varchar(255) NOT NULL,
                    visibility tinyint(1) unsigned NOT NULL,
                    time datetime DEFAULT '1000-01-01 00:00:00' NOT NULL,
                    PRIMARY KEY  (ID),
                    UNIQUE idx_user_id_title (user_id, list_title)
                ) %s;";
                static::createDBTable('kbp_favorite_lists', $sql);

                // wp_kbp_favorite_relationships
                $sql = "CREATE TABLE %s (
                    list_id int(11) unsigned NOT NULL,
                    post_id bigint(20) unsigned NOT NULL,
                    PRIMARY KEY  (list_id, post_id)
                ) %s;";
                static::createDBTable('kbp_favorite_relationships', $sql);
            });
        }


        private static function createDBTable($table_name, $sql)
        {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            global $wpdb;
            $sql = sprintf($sql, $wpdb->prefix . $table_name, $wpdb->get_charset_collate());

            dbDelta($sql);
        }


        public static function WPQuerySetBookOrder(WP_Query $query): WP_Query
        {
            $query->set('orderby', [
                'menu_order' => 'ASC',
                'ID' => 'ASC',
            ]);
            return $query;
        }

        /**
         * 获取所有书类型
         * @return \WP_Term[]|false
         */
        public static function allBookGenres(): array|false
        {
            return get_terms([
                'taxonomy' => BookPost::KBP_BOOK_GENRE,
                'hide_empty' => false,
            ]);
        }

        /**
         * 查找根文章
         * 用于从卷或章（子文章）找到所属书籍
         * @param \WP_Post|int $post 卷或章的对象或编号
         * @return \WP_Post|null 返回当前post的最根文章
         */
        public static function rootPost(WP_Post|int $post): WP_Post
        {
            $post = get_post($post);
            while ($post && $post->post_parent != 0)
                $post = get_post($post->post_parent);

            return $post;
        }

        /**
         * 以层次结构返回一本书的所有文章
         * 包含的字段：爷id，爹id, id, post标题(parent2_id, parent_id, ID, post_title)
         * @param WP_Post|int $book 
         * @param string[]|string|null $status 包含的wordpress文章状态
         * @return object[]|false 
         */
        public static function bookHierarchy(WP_Post|int $book, array|string|null $status = 'publish'): array|false
        {
            if ($book instanceof WP_Post)
                $book = $book->ID;

            if (!is_numeric($book))
                return false;

            // if (is_array($status))
            //     $status = implode(", ")

            $query = QueryBuilder::create()
                ->select('p2.post_parent as parent2_id')
                ->select('p1.post_parent as parent_id')
                ->select('p1.ID')
                ->select('p1.post_title')
                ->from('posts p1')
                ->join('posts p2', [['key_a' => 'p2.ID', 'key_b' => 'p1.post_parent']])
                ->where([
                    'raw' => "$book in (p1.post_parent, p2.post_parent)",
                    'p1.post_type' => BookPost::KBP_BOOK,
                ])
                ->order_by('p2.post_parent')
                ->order_by('p1.post_parent')
                ->order_by('p1.menu_order')
                ->order_by('p1.ID');

            if (is_array($status))
                $query->where(['p1.post_status' => ['operator' => 'IN', 'value' => $status]]);
            else if (is_string($status))
                $query->where(['p1.post_status' => $status]);
            else
                $query->where(['p1.post_status' => ['operator' => 'IN', 'value' => ['draft', 'publish', 'trash', 'future', 'pending', 'private']]]);


            // global $wpdb;
            // $table_name = $wpdb->prefix . 'posts';
            // // A sql query to return all post titles
            // $results = $wpdb->get_results($wpdb->prepare("
            // select      p2.post_parent as parent2_id,
            //             p1.post_parent as parent_id,
            //             p1.ID,
            //             p1.post_title
            // from        $table_name p1
            // left join   $table_name p2 on p2.ID = p1.post_parent 
            // where       %d in (p1.post_parent, p2.post_parent) 
            //             and p1.post_status = %s
            //             and p1.post_type = %s
            // order by    parent2_id, parent_id, p1.menu_order, p1.ID;", $book, $status, BookPost::KBP_BOOK));

            $results = $query->get();

            if (!$results)
                return false;

            return $results;
        }

        protected static function assertWpdbResult($result)
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
        public static function getUserFavoriteLists(WP_User|int $user, array $visibility = null, WP_Post|int $post = 0): array|false
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
        // public static function getUserPostFavorite(WP_Post|int $post, WP_User|int $user, array $visibility = null): array|false
        // {
        //     if ($post instanceof WP_Post) $post = $post->ID;
        //     if ($user instanceof WP_User) $user = $user->ID;
        //     if (!$user || !$post) return false;

        //     $res = QueryBuilder::create()
        //         ->select('ID')->select('list_title')
        //         ->from('kbp_favorite_lists l')
        //         ->join('kbp_favorite_relationships r', [['key_a' => 'l.ID', 'key_b' => 'r.list_id']], 'INNER')
        //         ->where(['l.user_id' => $user, 'r.post_id' => $post]);

        //     if ($visibility)
        //         $res->where([
        //             'visibility' => ['operator' => 'IN', 'value' => $visibility]
        //         ]);

        //     return $res->get();
        // }

        /**
         * 更新用户对某篇文章的收藏情况为给定列表
         * @param int[] $fav_list 这篇文章的所有收藏夹id
         */
        public static function updateUserPostFavorite(WP_Post|int $post, WP_User|int $user, array $fav_lists_id): bool
        {
            if ($post instanceof WP_Post) $post = $post->ID;
            if ($user instanceof WP_User) $user = $user->ID;

            // 用户的所有收藏夹
            $all_fav_lists = static::getUserFavoriteLists($user, null, $post);
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

                    static::assertWpdbResult($result);
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

                    static::assertWpdbResult($result);
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
        public static function createUserFavoriteList(WP_User|int $user, string $title, int $visibility = 0): int
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

                static::assertWpdbResult($result);

                return $wpdb->get_var("SELECT LAST_INSERT_ID()");
            } catch (Exception $e) {
                throw $e;
            }
        }

        /**
         * 获取某篇文章的收藏用户数
         * 不建议在循环中使用
         */
        public static function getFavoriteCount(WP_Post|int $id)
        {
            if ($id instanceof WP_Post) $id = $id->ID;

            // 尝试从wp缓存中读取
            $key = "book-$id-fav-count";
            $callback = fn () => QueryBuilder::create()
                ->from('kbp_favorite_lists f')
                ->join('kbp_favorite_relationships r', [['key_a' => 'f.id', 'key_b' => 'r.list_id']], 'INNER')
                ->where(['r.post_id' => $id])
                ->count('distinct user_id');

            return CacheHelpers::getOrSetCache($key, static::KBP_CACHE_DOMAIN, $callback);
        }

        /**
         * 获取用户对某文章的评分
         */
        public static function getBookUserRating(WP_Post|int $post, WP_User|int $user = 0): string|null
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

            return CacheHelpers::getOrSetCache($key, static::KBP_CACHE_DOMAIN, $callback);
        }

        /**
         * 获取文章的总评分
         * @return
         */
        public static function getBookRating(WP_Post|int $post): string|null
        {
            if ($post instanceof WP_Post) $post = $post->ID;

            $key = "book-$post-rating";
            $callback = fn () => QueryBuilder::create()->select('rating_avg')->from('kbp_postmeta')->where(['post_id' => $post])->value();

            return CacheHelpers::getOrSetCache($key, static::KBP_CACHE_DOMAIN, $callback);
        }


        /**
         * 设置用户对文章的评分
         */
        public static function setBookRating(WP_Post|int $post, WP_User|int $user, float $rating)
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

                static::assertWpdbResult($result);

                // 更新加权评分
                $table_posts = $wpdb->prefix . 'kbp_postmeta';
                $result = $wpdb->query($wpdb->prepare("
                insert into $table_posts (post_id, rating_weight, rating_avg)
                values      (%d, %d, %f)
                on duplicate key
                update      rating_avg = (rating_avg * rating_weight + %f) / (rating_weight + %d),
                rating_weight = rating_weight + %d;", $post, $weight, $rating, $weighted_rating, $weight, $weight));

                static::assertWpdbResult($result);

                $wpdb->query('COMMIT');
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }
        }

        public static function getLastVolumeID($book_id): int
        {
            // select IKD from wp_posts where post_parent=17 order by menu_order desc, post_title desc limit 1;
            $result = QueryBuilder::create()
                ->select('ID')
                ->from('posts posts')
                ->where(['post_parent' => $book_id], ['post_type' => BookPost::KBP_BOOK])
                ->order_by('menu_order', 'DESC')
                ->order_by('post_title', 'DESC')
                ->value();

            if (empty($result))
                return 0;
            return intval($result);
        }

        public static function setPostMenuOrder(array $IdOrderPairs): int
        {
            if (!count($IdOrderPairs))
                return 0;

            global $wpdb;

            $when = '';
            foreach ($IdOrderPairs as $id => $value) {
                $when .= $wpdb->prepare('WHEN ID=%d THEN %d ', $id, $value);
            }

            $in = implode(",", array_filter(array_keys($IdOrderPairs), fn ($item) => is_numeric($item)));

            $order_sql = "UPDATE {$wpdb->prefix}posts
                    SET menu_order = 
                    CASE
                    $when
                    END
                    WHERE ID in ($in);";



            $result = $wpdb->query($order_sql);

            static::assertWpdbResult($result);
            return intval($result);
        }

        /**
         * 书结点有：{id:int , volumes:[{volume1}, {volume2}, ...]}
         * 卷结点有：{id:int , ?chapters:[{chapter1}, {chapter2}, ...]}
         * 章结点有：{id:int}
         * @param array $bookHierarchy 
         * @return int 
         * @throws Exception 
         */
        public static function updateBookHierarchy(array $bookHierarchy): int
        {
            /**
             * 确保：
             * 1. 所有id均存在，且为数字，清除id不符合的项
             * 2. book至少有1个volume，
             * 3. volume可以不包含chapter
             */
            if (!array_key_exists('id', $bookHierarchy) || !is_numeric($bookHierarchy['id']) || empty($bookHierarchy['volumes']))
                return 0;


            // 清除无效volume

            foreach ($bookHierarchy['volumes'] as $vkey => $volume) {
                if (!array_key_exists('id', $volume) || !is_numeric($volume['id']))
                    unset($bookHierarchy['volumes'][$vkey]);
                else if (array_key_exists('chapters', $volume)) {
                    // 清除无效chapter
                    foreach ($volume['chapters'] as $ckey => $chapter) {
                        if (!array_key_exists('id', $chapter) || !is_numeric($chapter['id']))
                            unset($volume['chapters'][$ckey]);
                    }
                }
            }


            /**
             * 准备更新parent、order的数据
             */

            /**
             * idPairs: post-id => order
             */
            $idPairs = [];
            $query = QueryBuilder::create()->from('posts as posts');

            $bookId = $bookHierarchy['id'];


            // 决定volume的顺序
            $volumes = $bookHierarchy['volumes'];
            $volumeIds = array_column($volumes, 'id');
            $idPairs += array_flip($volumeIds);


            // （顺便组织SQL）更新卷的parent
            $parent_when = [];
            if (!empty($volumeIds))
                $parent_when[] = "WHEN ID in (" . implode(",", $volumeIds) . ") THEN " . intval($bookId);

            foreach ($volumes as $volume) {
                if (empty($volume['chapters']))
                    continue;

                // 决定chapter的顺序
                $chapterIds = array_column($volume['chapters'], 'id');
                $idPairs += array_flip($chapterIds);


                // （顺便组织SQL）更新章的parent
                $parent_when[] = "WHEN ID in (" . implode(',', $chapterIds) . ") THEN " . intval($volume['id']);
            }


            // 确定项来自这本书
            // 当作set用
            $bookPostIds = array_flip(array_map(fn ($item) => $item->ID, static::bookHierarchy($bookId, null)));
            foreach ($idPairs as $id => $order)
                if (!array_key_exists($id, $bookPostIds))
                    throw QueryException::fieldInvalid();



            // $idPairs = array_filter($idPairs, fn ($id) => array_key_exists($id, $bookPostIds), ARRAY_FILTER_USE_KEY);
            if (count($idPairs) === 0)
                return 0;

            // 压成负数（因为新建文章默认序列为0，应该是最新章节）
            $max = max($idPairs) + 1;
            foreach ($idPairs as &$idPair)
                $idPair -= $max;

                
            /**
             * 拼接SQL
             */
            if (count($parent_when))
                $query->set(['raw' => "posts.post_parent = CASE " . implode(' ', $parent_when) . " ELSE posts.post_parent END"]);

            global $wpdb;
            $when = '';
            foreach ($idPairs as $id => $value)
                $when .= $wpdb->prepare('WHEN ID=%d THEN %d ', $id, $value);

            $query->set(['raw' => "posts.menu_order = CASE $when ELSE posts.menu_order END"]);

            $query->where(['posts.ID' => ['operator' => 'IN', 'value' => array_filter(array_keys($idPairs), fn ($item) => is_numeric($item))]]);

            return $query->update();


            // QueryBuilder::create()
            // ->from('posts as posts')
            // ->set(['raw' => "WHEN ID in (" . implode(",", $volumeIds) . ") THEN $bookId"])
            // ->set(['raw' => "WHEN ID in (" . implode(",", $chapterIds) . ") THEN $volumeId"])
            // ->where(['posts.ID' => ['operator' => 'IN', 'value' => ???]])
            // ->update();
            // // volume
            // $volume_sql = "WHEN ID in (" . implode(",", $volumeIds) . ") THEN $bookId";

            // // chapter
            // $volume_sql = "WHEN ID in (" . implode(",", $chapterIds) . ") THEN $volumeId";
            // $parent_sql = "post_parent = CASE $volume_sql END"
        }
    }
}
