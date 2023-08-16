<?php

namespace KarsonJo\BookPost\SqlQuery {

    use Exception;
    use KarsonJo\BookPost\BookPost;
    use KarsonJo\Utilities\PostCache\CacheHelpers;
    use TenQuality\WP\Database\QueryBuilder;
    use WP_Post;
    use WP_Term;
    use WP_User;

    class BookQuery
    {
        const KBP_CACHE_DOMAIN = 'kbp_post_cache';
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
    }
}
