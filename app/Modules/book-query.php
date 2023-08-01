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
     * 筛选书籍
     */
    function get_book_by_filter()
    {
        // BD::class
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
     */

    use Illuminate\Http\Middleware\TrustProxies;
    use KarsonJo\BookPost\Book;
    use TenQuality\WP\Database\QueryBuilder;
    use Termwind\Components\Raw;
    use WP_Post;

    class BookFilterBuilder extends QueryBuilder
    {
        protected QueryBuilder $bookFilter;
        public function __construct($id = null)
        {
            parent::__construct($id);
            // $this->select('post_title')
            $this->from('posts posts')
                ->where([
                    'posts.post_type' => KBP_BOOK,
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

        public function of_id(int $id): BookFilterBuilder
        {
            $this->where(['posts.ID' => $id]);
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

            return array_map(function ($record) {
                return new WP_Post($record);
            }, $result);
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
            $result = $this->select('posts.ID')
                ->join('kbp_postmeta mt', [['key_a' => 'mt.post_id', 'key_b' => 'posts.ID']], 'LEFT')
                ->select('posts.post_author')
                ->select('posts.post_title')
                ->select('posts.post_excerpt')
                ->select('posts.post_date')
                ->select('mt.rating_avg rating')
                ->select('mt.word_count')
                ->get();

            // if ($with_meta || $with_thumbnail || $with_book_taxonomy)
            $post_ids = array_map(function ($record) {
                return $record->ID;
            }, $result);
            $this->cache_posts($post_ids);

            if ($with_meta)
                $this->cache_postmeta($post_ids);

            if ($with_thumbnail)
                $this->cache_thumbnail_status($post_ids);

            if ($with_book_taxonomy)
                $this->cache_taxonomy($post_ids);
            // update_object_term_cache($post_ids, KBP_BOOK);

            $this->build_cache();

            return array_map(function ($record) {
                return Book::initBookFromObject($record);
            }, $result);
            // return [];
        }

        //==================================================
        // 简单的预缓存支持

        protected $cached;

        protected function prepare_cache(string $key, array &$value)
        {
            // if ($key == 'posts') {
            //     print_r('[prepare_cache]');
            //     print_r("[$key]");
            //     print_r($value);
            //     print_r(isset($this->cached[$key]) ? $this->cached[$key] : "[empty]");
            // }

            if (!isset($this->cached[$key]))
                $this->cached[$key] = [];

            foreach ($value as $var)
                if (!isset($this->cached[$key][$var]))
                    $this->cached[$key][$var] = $var;

            // if ($key == 'posts') {
            //     print_r(isset($this->cached[$key]) ? $this->cached[$key] : "[empty]");
            // }
        }

        protected function cache_posts(array &$post_ids)
        {
            // get_posts([
            //     'include' => $post_ids,
            //     // 'post_type' => 'attachment',
            // ]);
            // $this->cached['posts'] += $post_ids;
            $this->prepare_cache('posts', $post_ids);
        }

        /**
         * 统一缓存所有给定文章的postmeta
         * https://hitchhackerguide.com/2011/11/01/reducing-postmeta-queries-with-update_meta_cache/
         * @param int[] &$post_ids
         */
        protected function cache_postmeta(array &$post_ids)
        {
            // update_meta_cache('post', $post_ids);
            $this->prepare_cache('postmeta', $post_ids);
        }

        /**
         * 统一缓存给定文章的特色图片状态
         * thumbnail post + postmeta
         * @param int[] &$post_ids
         */
        protected function cache_thumbnail_status(array &$post_ids)
        {
            // //必须先查metadata
            // $this->cache_postmeta($post_ids);
            // // 获取所有特色图片的id
            // $thumb_ids = array_map(function ($post_id) {
            //     return (int) get_post_meta($post_id, '_thumbnail_id', true);
            // }, $post_ids);
            // // 缓存所有特色图片所对应的文章（统一查询一遍）
            // get_posts([
            //     'include' => $thumb_ids,
            //     'post_type' => 'attachment',
            // ]);
            // // 缓存所有的postmeta                
            // $this->cache_postmeta($thumb_ids);

            // $this->cached['postmeta'] += $post_ids;
            $this->prepare_cache('thumbnail', $post_ids);
        }

        /**
            
         * 统一所有给定文章作为[object_type]类型的“所有”taxonomy
         * @param int[] &$post_ids
         */
        protected function cache_taxonomy(array &$post_ids)
        {
            // update_object_term_cache($post_ids, $object_type);
            $this->prepare_cache('taxonomy', $post_ids);
        }

        protected function build_cache()
        {
            // print_r('[build_cache]');
            // postmeta
            if (isset($this->cached['postmeta']) || isset($this->cached['thumbnail'])) {
                $posts = $this->cached['postmeta'] + $this->cached['thumbnail'];
                update_meta_cache('post', $posts);
                // print_r('[build_cache]');
            }

            if (isset($this->cached['thumbnail'])) {
                // 需要获取postmeta
                $thumb_ids = array_map(function ($post_id) {
                    return (int) get_post_meta($post_id, '_thumbnail_id', true);
                }, $this->cached['thumbnail']);

                // 缓存post
                // $this->cached['posts'] += $thumb_ids;
                $this->prepare_cache('posts', $thumb_ids);
                // print_r($this->cached['posts']);
                // 再次缓存postmeta
                update_meta_cache('post', $thumb_ids);
            }

            if (isset($this->cached['posts']))
                // $this->cache_posts($cached['posts']);
                get_posts([
                    'include' => $this->cached['posts'],
                    'post_type' => 'any',
                    'post_status' => 'any',
                ]);

            if (isset($this->cached['taxonomy']))
                update_object_term_cache($this->cached['taxonomy'], KBP_BOOK);
        }


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
