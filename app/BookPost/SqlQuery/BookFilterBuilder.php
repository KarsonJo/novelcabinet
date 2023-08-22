<?php

namespace KarsonJo\BookPost\SqlQuery {


    /**
     * query cheatsheet
     * 查找所有类别的id与对应名称
     * select wp_terms.term_id, wp_term_taxonomy.term_taxonomy_id, name from wp_terms inner join wp_term_taxonomy on wp_terms.term_id = wp_term_taxonomy.term_id;
     * 
     * 查找类别id为3所有文章
     * select post_title from wp_posts inner join wp_term_relationships on object_id = wp_posts.ID where term_taxonomy_id = 3;
     * 
     * 查找最近3个月
     * select now() now,  now() - interval 3 month "last 3 month", last_day(now()) + interval 1 day - interval 3 month "last 3 month from day1";
     * 
     * 查一天开始
     * SELECT DATE_FORMAT(now(), '%Y-%m-%d');
     * 
     * [id, name, parent_id]：查找所有东西的所有孙子（19是所有东西的共同父亲）
     * SELECT p.id, count(gc.name) FROM products p JOIN products c ON c.parent_id = p.id JOIN products gc ON gc.parent_id = c.id WHERE p.parent_id = 19 GROUP BY p.id
     * 
     * 查找书的信息（书+所有章节，3层结构），废了
     * select p.ID post_grandparent , gc.ID "chapter ID", gc.post_title "chapter name", gc.post_modified_gmt date from wp_posts p JOIN wp_posts c ON c.post_parent = p.ID JOIN wp_posts gc ON gc.post_parent = c.ID where p.post_parent = 0 order by date;
     * select p.ID post_grandparent , gc.ID "chapter ID", gc.post_title "chapter name", gc.menu_order "chapter order", gc.post_modified_gmt date from wp_posts p JOIN wp_posts c ON c.post_parent = p.ID JOIN wp_posts gc ON gc.post_parent = c.ID where p.post_parent = 0 order by "chapter order";
     * 
     * 查找书的最后更新时间，和解！（应用层维护）
     * EXPLAIN select post_title, post_date from wp_posts where post_type = 'BOOK' and post_status = 'publish' and post_parent = 0 order by post_date;
     * 
     * 按评分排序
     * select ID, post_title, post_date from wp_posts inner join wp_kbp_po  where post_parent = 0 and post_type = 'BOOK' and post_status = 'publish' and post_date between now() - interval 3 month and now();
     * 
     * 查询某用户收藏夹
     * select post_title, list_title from wp_posts posts inner join wp_kbp_favorite_relationships fav_r on posts.ID = fav_r.post_id join wp_kbp_favorite_lists fav_l on fav_r.list_id = fav_l.ID where fav_l.user_id = 1;
     *
     * 查询文章表中某篇文章对应用户收藏数
     * SELECT COUNT(DISTINCT user_id) AS num_of_users FROM wp_kbp_favorite_lists F INNER JOIN wp_kbp_favorite_relationships R ON F.id = R.list_id WHERE R.post_id = '9';
     * 
     * 查询“文章表”中所有文章ID的对应用户收藏数（同用户多次收藏去重）
     * SELECT P.ID AS post_id, COUNT(DISTINCT F.user_id) AS num_of_users FROM wp_posts P LEFT JOIN wp_kbp_favorite_relationships R ON P.ID = R.post_id LEFT JOIN wp_kbp_favorite_lists F ON R.list_id = F.ID GROUP BY P.ID HAVING num_of_users > 0;
     */

    use Exception;
    use KarsonJo\BookPost\Book;
    use KarsonJo\BookPost\BookPost;
    use KarsonJo\Utilities\PostCache\CacheBuilder;
    use TenQuality\WP\Database\QueryBuilder;
    use WP_Post;
    use WP_User;

    class BookFilterBuilder extends QueryBuilder
    {
        protected QueryBuilder $bookFilter;
        public function __construct($id = null)
        {
            parent::__construct($id);
            // $this->select('post_title')
            $this->from('posts posts')
                ->where([
                    'posts.post_type' => BookPost::KBP_BOOK,
                    'posts.post_parent' => 0,
                ]);
        }

        /**
         * 创建一个查询构造器。
         * @param string|null $id 构造器的id
         * @param bool $published 是否默认选择已发布文章
         */
        public static function create($id = null, bool $published = true): BookFilterBuilder
        {
            $builder = new self($id);
            $builder->no_auto_draft();
            if ($published)
                $builder->published();
            return $builder;
        }

        public function join($table, $args, $type = false, $add_prefix = true): BookFilterBuilder
        {
            global $wpdb;

            $repeated = in_array(($add_prefix ? $wpdb->prefix : '') . $table, array_column($this->builder['join'], 'table'));

            if ($repeated)
                return $this;

            parent::join($table, $args, $type, $add_prefix);
            return $this;
        }

        public function get($output = OBJECT, $callable_mapping = null, $calc_rows = false)
        {
            if (array_key_exists('page', $this->builder) && $this->builder['page'] > 0)
                $this->builder['offset'] += ($this->builder['page'] - 1) * $this->builder['limit'];
            return parent::get($output, $callable_mapping, $calc_rows);
        }

        /**
         * 页码，需要结合limit使用
         * 
         * [warning]目前只在get()查询中生效
         * @param int $page 页码，从1数起
         */
        public function page(int $page): BookFilterBuilder
        {
            $this->builder['page'] = $page;
            return $this;
        }

        //==================================================
        // 预制查询选项

        /**
         * 筛选已发布的书籍
         */
        public function published(): BookFilterBuilder
        {
            $this->where([
                'post_status' => 'publish',
            ]);
            return $this;
        }

        /**
         * 剔除自动草稿 
         */
        public function no_auto_draft(): BookFilterBuilder
        {
            $this->where( [
                'post_status' => [
                    'operator' => '<>',
                    'value'    => 'auto-draft',
                ],
            ]);
            return $this;
        }

        public function of_id(int|WP_Post $id): BookFilterBuilder
        {
            if ($id instanceof WP_Post)
                $id = $id->ID;
            $this->where(['posts.ID' => $id]);
            return $this;
        }

        public function of_author(int|WP_User $author): BookFilterBuilder
        {
            if ($author instanceof WP_User)
                $author = $author->ID;
            $this->where(['posts.post_author' => $author]);
            return $this;
        }

        /**
         * 筛选含有至少一个指定标签的书籍
         * @param int|int[] $genreIds taxonomy类型：genre
         */
        public function of_any_genres(int|array $genreIds): BookFilterBuilder
        {
            if (is_int($genreIds))
                $genreIds = [$genreIds];

            $this->join('term_relationships', [
                [
                    'key_a' => 'object_id',
                    'key_b' => 'posts.ID',
                ]
            ])
                ->where([
                    'term_taxonomy_id' => ['operator' => 'IN', 'value' => $genreIds],
                ]);
            return $this;
        }

        /**
         * 筛选含有所有给定标签的书籍
         * @param int|int[] $genreIds taxonomy类型：genre

         */
        public function of_all_genres(int|array $genreIds): BookFilterBuilder
        {
            // 转换字符串数字、剔除其它无效元素、去重
            $sanitized = [];
            foreach ($genreIds as $genreId) {
                if (is_string($genreId)) {
                    $genreId = intval($genreId);
                    if ($genreId == 0)
                        continue;
                }
                if (is_int($genreId))
                    $sanitized[$genreId] = $genreId;
            }


            $str = implode(',', $sanitized);
            $raw = "SELECT NULL
            FROM wp_posts __p
            JOIN wp_term_relationships __t ON __p.ID = __t.object_id
            WHERE __t.term_taxonomy_id IN ($str)
            AND posts.ID = __p.id
            GROUP BY __p.ID
            HAVING COUNT(1) = " . count($sanitized);


            // $raw = "SELECT tg.post_id
            // FROM TAGGINGS tg
            // JOIN TAGS t ON t.id = tg.tag_id
            // WHERE t.name IN ($str)
            // GROUP BY tg.post_id
            // HAVING COUNT(DISTINCT t.name) = " . count($genreIds);





            $this->where([
                'raw' => "EXISTS ($raw)",
            ]);
            return $this;
        }

        /**
         * 指定查询某段时间内更新的书籍
         * 参数范围详见函数实现，超出范围视为无效
         * @param int $index 选项的索引值
         */
        public function of_latest(int $index): BookFilterBuilder
        {
            $date = current_datetime()->format('Y-m-d');
            $now = current_datetime()->format('Y-m-d H:i:s');
            $query = "post_date between '$date' - interval %s and '$now'";
            switch ($index) {
                case 1:
                    $query = sprintf($query, '3 day');
                    break;
                case 2:
                    $query = sprintf($query, '7 day');
                    break;
                case 3:
                    $query = sprintf($query, '15 day');
                    break;
                case 4:
                    $query = sprintf($query, '1 month');
                    break;
                case 5:
                    $query = sprintf($query, '3 month');
                    break;
                default:
                    return $this;
            }
            $this->where([
                'raw' => $query,
            ]);
            return $this;
        }

        /**
         * 按时间排序
         */
        public function order_by_time(bool $ASC = false): BookFilterBuilder
        {
            $this->order_by('post_date', $ASC ? 'ASC' : 'DESC');
            return $this;
        }

        /**
         * 按评分排序
         */
        public function order_by_rating(bool $ASC = false): BookFilterBuilder
        {
            // print_r(123);
            // print_r(array_search('wp_kbp_postmeta mt', array_column($this->builder['join'], 'table')));
            // print_r(123);
            $this->join('kbp_postmeta mt', [['key_a' => 'mt.post_id', 'key_b' => 'posts.ID']], 'LEFT')
                ->order_by('mt.rating_avg', $ASC ? 'ASC' : 'DESC');
            // print_r($this->builder['join']);
            return $this;
        }

        /**
         * 在给定用户的收藏夹中
         * @param int $user 用户，默认为当前用户
         */
        public function in_favourite(int $user = 0)
        {
            /** 
             * select post_title, list_title from wp_posts posts 
             * inner join wp_kbp_favorite_relationships fav_r on posts.ID = fav_r.post_id 
             * innor join wp_kbp_favorite_lists fav_l on fav_r.list_id = fav_l.ID
             * where fav_l.user_id = 1
             * */

            $this->join('kbp_favorite_relationships fav_r', [['key_a' => 'posts.ID', 'key_b' => 'fav_r.post_id']], 'INNER')
                ->join('kbp_favorite_lists fav_l', [['key_a' => 'fav_r.list_id', 'key_b' => 'fav_l.ID']], 'INNER')
                ->where(['fav_l.user_id' => $user == 0 ? get_current_user_id() : $user]);
            return $this;
        }

        //==================================================
        // 获取结果

        /**
         * @return WP_Post[] 以WP_Post形式返回查询结果
         */
        public function get_as_post(): array
        {
            $this->builder['select'] = []; //clear select
            $result = $this->select('posts.*')->get();

            return array_map(fn ($record) => new WP_Post($record), $result);
        }

        /**
         * 以Book形式返回查询结果
         * 由于部分变量由WordPress内置函数给出，为了避免(N+1)查询，该函数提供统一预缓存
         * @param bool $with_meta 并统一预先查出post的meta信息
         * @param bool $with_thumbnail 并统一预先查出封面图(thumbnail post)及其meta信息
         * @param bool $with_book_taxonomy 并统一预先查出book类型的分类法
         * @return Book[]
         */
        public function get_as_book(bool $with_meta = true, $with_thumbnail = true, $with_book_taxonomy = true): array
        {
            $this->builder['select'] = []; //clear select
            $result = $this->select('distinct posts.ID')
                ->join('kbp_postmeta mt', [['key_a' => 'mt.post_id', 'key_b' => 'posts.ID']], 'LEFT')
                ->select('posts.post_author')
                ->select('posts.post_title')
                ->select('posts.post_excerpt')
                ->select('posts.post_modified update_date')
                ->select('posts.post_status')
                ->select('mt.rating_weight')
                ->select('mt.rating_avg rating')
                ->select('mt.word_count')
                ->get();

            // if ($with_meta || $with_thumbnail || $with_book_taxonomy)
            $post_ids = array_map(fn ($record) => $record->ID, $result);

            $cacher = CacheBuilder::create();

            $cacher->cachePosts($post_ids);
            if ($with_meta)
                $cacher->cachePostmeta($post_ids);
            if ($with_thumbnail)
                $cacher->cacheThumbnailStatus($post_ids);
            if ($with_book_taxonomy)
                $cacher->cacheTaxonomy($post_ids);

            $cacher->cache();

            // $this->cache_posts($post_ids);

            // if ($with_meta)
            //     $this->cache_postmeta($post_ids);

            // if ($with_thumbnail)
            //     $this->cache_thumbnail_status($post_ids);

            // if ($with_book_taxonomy)
            //     $this->cache_taxonomy($post_ids);

            // $this->build_cache();

            return array_map(fn ($record) => Book::initBookFromObject($record), $result);
            // return [];
        }

        //==================================================
        // 简单的预缓存支持

        // protected $cached;

        // protected function prepare_cache(string $key, array &$value)
        // {
        //     // if ($key == 'posts') {
        //     //     print_r('[prepare_cache]');
        //     //     print_r("[$key]");
        //     //     print_r($value);
        //     //     print_r(isset($this->cached[$key]) ? $this->cached[$key] : "[empty]");
        //     // }

        //     if (!isset($this->cached[$key]))
        //         $this->cached[$key] = [];

        //     foreach ($value as $var)
        //         if (!isset($this->cached[$key][$var]))
        //             $this->cached[$key][$var] = $var;

        //     // if ($key == 'posts') {
        //     //     print_r(isset($this->cached[$key]) ? $this->cached[$key] : "[empty]");
        //     // }
        // }

        // protected function cache_posts(array &$post_ids)
        // {
        //     // get_posts([
        //     //     'include' => $post_ids,
        //     //     // 'post_type' => 'attachment',
        //     // ]);
        //     // $this->cached['posts'] += $post_ids;
        //     $this->prepare_cache('posts', $post_ids);
        // }

        // /**
        //  * 统一缓存所有给定文章的postmeta
        //  * https://hitchhackerguide.com/2011/11/01/reducing-postmeta-queries-with-update_meta_cache/
        //  * @param int[] &$post_ids
        //  */
        // protected function cache_postmeta(array &$post_ids)
        // {
        //     // update_meta_cache('post', $post_ids);
        //     $this->prepare_cache('postmeta', $post_ids);
        // }

        // /**
        //  * 统一缓存给定文章的特色图片状态
        //  * thumbnail post + postmeta
        //  * @param int[] &$post_ids
        //  */
        // protected function cache_thumbnail_status(array &$post_ids)
        // {
        //     // //必须先查metadata
        //     // $this->cache_postmeta($post_ids);
        //     // // 获取所有特色图片的id
        //     // $thumb_ids = array_map(function ($post_id) {
        //     //     return (int) get_post_meta($post_id, '_thumbnail_id', true);
        //     // }, $post_ids);
        //     // // 缓存所有特色图片所对应的文章（统一查询一遍）
        //     // get_posts([
        //     //     'include' => $thumb_ids,
        //     //     'post_type' => 'attachment',
        //     // ]);
        //     // // 缓存所有的postmeta                
        //     // $this->cache_postmeta($thumb_ids);

        //     // $this->cached['postmeta'] += $post_ids;
        //     $this->prepare_cache('thumbnail', $post_ids);
        // }

        // /**

        //  * 统一所有给定文章作为[object_type]类型的“所有”taxonomy
        //  * @param int[] &$post_ids
        //  */
        // protected function cache_taxonomy(array &$post_ids)
        // {
        //     // update_object_term_cache($post_ids, $object_type);
        //     $this->prepare_cache('taxonomy', $post_ids);
        // }

        // protected function build_cache()
        // {
        //     // print_r('[build_cache]');
        //     // postmeta
        //     if (isset($this->cached['postmeta']) || isset($this->cached['thumbnail'])) {
        //         $posts = $this->cached['postmeta'] + $this->cached['thumbnail'];
        //         update_meta_cache('post', $posts);
        //         // print_r('[build_cache]');
        //     }

        //     if (isset($this->cached['thumbnail'])) {
        //         // 需要获取postmeta
        //         $thumb_ids = array_map(function ($post_id) {
        //             return (int) get_post_meta($post_id, '_thumbnail_id', true);
        //         }, $this->cached['thumbnail']);

        //         // 缓存post
        //         // $this->cached['posts'] += $thumb_ids;
        //         $this->prepare_cache('posts', $thumb_ids);
        //         // print_r($this->cached['posts']);
        //         // 再次缓存postmeta
        //         update_meta_cache('post', $thumb_ids);
        //     }

        //     if (isset($this->cached['posts']))
        //         // $this->cache_posts($cached['posts']);
        //         get_posts([
        //             'include' => $this->cached['posts'],
        //             'post_type' => 'any',
        //             'post_status' => 'any',
        //         ]);

        //     if (isset($this->cached['taxonomy']))
        //         update_object_term_cache($this->cached['taxonomy'], KBP_BOOK);
        // }


        //==================================================

        /**
         * [DEBUG] 获取组装后的查询语句
         */
        public function debug_get_sql(): string
        {
            $call = function (string $name, string &$query) {
                $reflector = new \ReflectionObject($this);
                $method = $reflector->getMethod($name);
                $method->setAccessible(true);
                echo $method->invokeArgs($this, [&$query]);
            };

            $query = '';
            $call('_query_select', $query);
            $call('_query_from', $query);
            $call('_query_join', $query);
            $call('_query_where', $query);
            $call('_query_group', $query);
            $call('_query_having', $query);
            $call('_query_order', $query);
            $call('_query_limit', $query);
            $call('_query_offset', $query);

            return $query;
        }
    }
}
