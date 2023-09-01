<?php

namespace KarsonJo\BookPost\SqlQuery {

    use Exception;
    use KarsonJo\BookPost\Book;
    use KarsonJo\BookPost\BookContents;
    use KarsonJo\BookPost\BookMeta\MetaManager;
    use KarsonJo\BookPost\BookPost;
    use KarsonJo\Utilities\Algorithms\StringAlgorithms;
    use KarsonJo\Utilities\Debug\Logger;
    use KarsonJo\Utilities\PostCache\CacheHelpers;
    use NovelCabinet\Utilities\ArrayHelper;
    use TenQuality\WP\Database\QueryBuilder;
    use WP_Error;
    use WP_Post;
    use WP_Query;
    use WP_Term;
    use WP_User;

    use function KarsonJo\BookPost\book_database_init;

    class BookQuery
    {
        const KBP_CACHE_DOMAIN = 'kbp_post_cache';
        private static int $recursionDepth = 1000;


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
        public static function rootPost(WP_Post|int $post): ?WP_Post
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

        /**
         * 抛出异常如果$result是false或者WP_Error
         * @param mixed $result 
         * @return void 
         * @throws QueryException 
         */
        protected static function assertWpdbResult($result)
        {
            global $wpdb;
            if ($result === false)
                throw QueryException::wpdbException($wpdb->last_error);
            else if ($result instanceof WP_Error)
                throw QueryException::wpdbException($result->get_error_message());
        }

        /**
         * 按给定字段查询一本书
         * @param string[]|int $args [$key => $value] 只接受等式；整数，书卷章的id
         * @return null|Book 
         * @throws Exception 
         */
        public static function getBook(array|int $args): ?Book
        {
            if (!is_array($args) && !is_numeric($args))
                return null;

            $query = BookFilterBuilder::create(null, false);

            if (is_numeric($args)) {
                $id = BookQuery::rootPost($args);
                if (!$id) return null;

                $query->of_id($id);
            } else if (is_array($args)) {
                foreach ($args as $key => $value)
                    $query->where([$key => $value]);
            }

            $query->limit(1);
            $book = $query->get_as_book();
            if (!$book)
                return null;

            return $book[0];
        }

        /**
         * 
         * @param string[] $args 
         * @return Book[]
         * @throws Exception 
         */
        public static function getBooks(array $args): array
        {
            $query = BookFilterBuilder::create(null, false);
            foreach ($args as $key => $value)
                $query->where([$key => $value]);

            return $query->get_as_book();
        }

        /**
         * 查找“相似”书籍
         * 精确匹配书籍，模糊匹配作者
         * 精确匹配作者，模糊匹配书籍
         * @param string $keywordTitle 书籍名称
         * @param null|string $keywordAuthor 作者用户名，忽略则完全不考虑作者名匹配
         * @param int $titleThreshold 模糊匹配标题时，允许的误差
         * @param int $authorThreshold 模糊匹配作者时，允许的误差
         * @return null|Book 
         * @throws Exception 
         * @throws QueryException 
         */
        public static function bookSimilarMatch(string $keywordTitle, ?string $keywordAuthor = null, $titleThreshold = 2, $authorThreshold = 2): ?Book
        {
            // 必须指定title
            if (!$keywordTitle)
                return null;

            /**
             * 精准匹配title，模糊匹配author
             */
            $books = BookQuery::getBooks(['post_title' => $keywordTitle]);

            if (count($books) > 0) {
                /**
                 * 书名有匹配，一定会返回一本
                 * 如果有多本，返回作者名最相似的
                 * 如果没给定作者名，那摆烂返回第一本
                 */
                if (!$keywordAuthor)
                    return $books[0];

                [$book, $value] = ArrayHelper::minBy(
                    $books,
                    fn (Book $book) => StringAlgorithms::levenshteinWithThreshold($book->authorLogin, $keywordAuthor, $authorThreshold, PHP_INT_MAX),
                    0
                );

                return $book;
            }

            /**
             * 精准匹配author，模糊匹配title
             */
            if ($keywordAuthor && $titleThreshold > 0) {
                $authorId = AuthorQuery::getAuthorID($keywordAuthor);
                $books = BookQuery::getBooks(['post_author' => $authorId]);
                if (count($books) > 0) {
                    /**
                     * 作者名有匹配，不一定就返回书本
                     * 返回书名误差最小，且在阈值之内的一本
                     */
                    [$book, $value] = ArrayHelper::minBy(
                        $books,
                        fn (Book $book) => StringAlgorithms::levenshteinWithThreshold($book->title, $keywordTitle, $titleThreshold, PHP_INT_MAX),
                        0
                    );

                    if ($value <= $titleThreshold)
                        return $book;
                }
            }

            return null;
        }


        /**
         * 插入整本书
         * todo: 也许应该改成一次transaction原子化操作
         * @param mixed $book 书的数据（格式以后补）
         * @return string[] [$bookId, $report]
         * @throws Exception 
         */
        public static function createBook($book): array
        {
            // print_r(json_encode($book));
            // return;
            $report = [];
            // $functionWatch = new StopWatch();
            // $stepWatch = new StopWatch();
            // $progressWatch = new StopWatch();
            $logger = new Logger();
            $logger->registerContext("steps");
            $logger->registerContext("progress");
            $logger->registerContext("summary");
            $logger->resetContexts();

            // print_r("start creating a book\n");

            /**
             * 准备插入
             * 可能涉及数千篇文章的插入
             * 为了兼容起见，还是使用WordPress提供的函数进行插入
             * 但为了提高效率，必须做出一些优化
             * https://wordpress.stackexchange.com/questions/102349/faster-way-to-wp-insert-post-add-post-meta-in-bulk
             */
            // 停止计数
            wp_defer_term_counting(true);
            wp_defer_comment_counting(true);

            // 截胡slug的查找，直接给定一个本机唯一id
            $uniSlug = fn ($override_slug) => $override_slug ?? uniqid();
            add_filter('pre_wp_unique_post_slug', $uniSlug);

            // 不知道什么鬼用的pingback
            remove_action('do_pings', 'do_all_pings', 10);

            //无限时间
            $originalTimeLimit = ini_get('max_execution_time');
            set_time_limit(9999);



            /**
             * 插入书和卷
             */
            global $wpdb;
            try {
                $wpdb->query('START TRANSACTION;');

                $authorId = AuthorQuery::getAuthorID($book['author'], true);
                // book
                $newBookId = wp_insert_post([
                    'post_title' => $book['title'],
                    'post_excerpt' => $book['excerpt'],
                    'post_author' => $authorId,
                    'post_type' => BookPost::KBP_BOOK,
                    'tax_input' => [
                        BookPost::KBP_BOOK_GENRE => $book['genres']
                    ],
                    'tags_input' => $book['tags'],
                    'post_status' => 'publish',
                ], true);

                static::assertWpdbResult($newBookId);

                // print_r("\t\tinsrted book: {$stepWatch->getElapsedStringAndReset()}\n");
                // $report['steps'][] = "[book] insrted: {$stepWatch->getElapsedStringAndReset()}";
                $logger->addLog('steps', 'steps', 'book', 'inserted');


                // volumes
                $volumeNameIdMap = [];
                $volumes = $book['volumes'];
                foreach ($volumes as $volume) {
                    $newVolumeId = wp_insert_post([
                        'post_title' => $volume['title'],
                        'post_parent' => $newBookId,
                        'post_author' => $authorId,
                        'post_type' => BookPost::KBP_BOOK,
                        'post_status' => 'publish',
                    ], true);

                    static::assertWpdbResult($newVolumeId);

                    $volumeNameIdMap[$volume['title']] = $newVolumeId;
                }

                // print_r("\t\tinserted volumes: {$stepWatch->getElapsedStringAndReset()}\n");
                // $report['steps'][] = "[volumes] insrted: {$stepWatch->getElapsedStringAndReset()}";
                $logger->addLog('steps', 'steps', 'volumes', 'inserted');


                $wpdb->query('COMMIT;');
                // $report['steps'][] = "[commit] above: {$stepWatch->getElapsedStringAndReset()}";
                $logger->addLog('steps', 'steps', 'commit', 'above');
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK;');
                throw $e;
            }

            // print_r("\tinserted book & volumes: {$progressWatch->getElapsedStringAndReset()}\n");
            // $report['summary'][] = "[progress] inserted book & volumes: {$progressWatch->getElapsedStringAndReset()}";
            $logger->addLog('summary', 'progress', 'progress', 'inserted book & volumes');


            /**
             * 按批插入章节
             */
            try {
                $batchSize = 100;
                foreach ($volumes as $volume) {
                    $batches = array_chunk($volume['chapters'], $batchSize);
                    foreach ($batches as $chapterBatch) {
                        $wpdb->query('START TRANSACTION;');
                        foreach ($chapterBatch as $chapter) {
                            $newChapterId = wp_insert_post([
                                'post_title' => $chapter['title'],
                                'post_content' => $chapter['content'] ?? '',
                                'post_parent' => $volumeNameIdMap[$volume['title']],
                                'post_author' => $authorId,
                                'post_type' => BookPost::KBP_BOOK,
                                'post_status' => 'publish',
                            ], true);

                            static::assertWpdbResult($newChapterId);
                        }
                        // print_r("\t\tinsrted " . count($chapterBatch) . " chapters: {$stepWatch->getElapsedStringAndReset()}\n");
                        // $report['steps'][] = "[chapters] " . count($chapterBatch) . " insrted: {$stepWatch->getElapsedStringAndReset()}";
                        $logger->addLog('steps', 'steps', 'chapters', count($chapterBatch) . ' inserted');


                        $wpdb->query('COMMIT;');
                        // $report['steps'][] = "[commit] above: {$stepWatch->getElapsedStringAndReset()}";
                        $logger->addLog('steps', 'steps',  'commit', 'above');
                    }
                }
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK;');
                throw $e;
            } finally {
                /**
                 * 恢复原来设定
                 */
                wp_defer_term_counting(false);
                wp_defer_comment_counting(false);

                remove_filter('pre_wp_unique_post_slug', $uniSlug);

                add_action('do_pings', 'do_all_pings', 10, 0);

                set_time_limit($originalTimeLimit);
            }

            // print_r("\tinserted all chapters: {$progressWatch->getElapsedStringAndReset()}\n");
            // $report['summary'][] = "[progress] chapters inserted: {$progressWatch->getElapsedStringAndReset()}";
            $logger->addLog('summary', 'progress', 'progress', 'inserted chapters');

            // print_r("insert function executing time: {$functionWatch->getElapsedStringAndReset()}\n");
            // $report['summary'][] = "[function] insert executing time: {$functionWatch->getElapsedStringAndReset()}";
            $logger->addLog('summary', 'summary', 'function', 'completed');



            // return [$newBookId, $report];
            return [$newBookId, $logger->getLog()];
        }

        /**
         * 更新整本书
         * todo: 目前只是雏形，只会更新现有内容+append新增内容
         * 对于删除内容，目前只是摆烂
         * @return void 
         */
        // public static function updateBook($book, Book $original)
        // {
        //     wp_defer_term_counting(true);
        //     wp_defer_comment_counting(true);

        //     // 截胡slug的查找，直接给定一个本机唯一id
        //     $uniSlug = fn ($override_slug) => $override_slug ?? uniqid();
        //     add_filter('pre_wp_unique_post_slug', $uniSlug);

        //     // 不知道什么鬼用的pingback
        //     remove_action('do_pings', 'do_all_pings', 10);

        //     //无限时间
        //     $originalTimeLimit = ini_get('max_execution_time');
        //     set_time_limit(9999);

        //     //书meta
        //     //卷meta
        //     //目录顺序
        //     /**
        //      * 插入书和卷
        //      */
        //     global $wpdb;
        //     try {
        //         $wpdb->query('START TRANSACTION;');

        //         // $authorId = AuthorQuery::getAuthorID($book['author']);
        //         // book
        //         $authorId = $original->authorId;
        //         $bookMeta = [];

        //         if (!empty($book['title']) && $book['title'] != $original->title)
        //             $bookMeta['post_title'] = $book['title'];

        //         if (!empty($book['excerpt']) && $book['excerpt'] != $original->excerpt)
        //             $bookMeta['post_excerpt'] = $book['excerpt'];

        //         if (!empty($book['genres'])) {
        //             $newGenres = $book['genres'];
        //             $oldGenres = array_map(fn (WP_Term $wp_term) => $wp_term->term_id, $original->genres);
        //             $merged = array_merge($newGenres, $oldGenres);
        //             if (!ArrayHelper::arrayValuesEqual($oldGenres, $newGenres))
        //                 $bookMeta['tax_input'] = [BookPost::KBP_BOOK_GENRE => $merged];
        //         }

        //         if (!empty($book['tags'])) {
        //             $newTags = $book['tags'];
        //             $oldTags = array_map(fn (WP_Term $wp_term) => $wp_term->name, $original->tags);
        //             $merged = array_merge($newTags, $oldTags);
        //             if (!ArrayHelper::arrayValuesEqual($oldTags, $newTags))
        //                 $bookMeta['tags_input'] = $merged;
        //         }

        //         if ($bookMeta) {
        //             $bookMeta['ID'] = $original->ID;
        //             $result = wp_update_post($bookMeta, true);
        //             static::assertWpdbResult($result);
        //         }


        //         // volumes
        //         /**
        //          * 更新volumes
        //          * 给了volume项默认即需要更新
        //          */
        //         $originalContents = $original->contents;
        //         $volumeNameIdMap = [];
        //         $volumes = $book['volumes'];
        //         foreach ($volumes as $volume) {
        //             if (!empty($volume['title'])) {
        //                 // 查找相似名称
        //                 [$volumeId, $similarity] = $originalContents->idBySimilarName('volume', $volume['title'], 2);

        //                 // 存在相似
        //                 if ($volumeId) {
        //                     // 且不同：更新
        //                     if ($similarity > 0) {
        //                         $volumeId = wp_update_post([
        //                             'ID' => $volumeId,
        //                             'post_title' => $volume['title']
        //                         ], true);
        //                     }
        //                     // 否则，不需要做任何事
        //                 }
        //                 // 不存在：插入
        //                 else {
        //                     $volumeId = wp_insert_post([
        //                         'post_title' => $volume['title'],
        //                         'post_parent' => $original->ID,
        //                         'post_author' => $authorId,
        //                         'post_type' => BookPost::KBP_BOOK,
        //                         'post_status' => 'publish',
        //                     ], true);
        //                 }
        //                 static::assertWpdbResult($volumeId);

        //                 $volumeNameIdMap[$volume['title']] = $volumeId;
        //             }
        //         }

        //         $wpdb->query('COMMIT;');
        //     } catch (Exception $e) {
        //         $wpdb->query('ROLLBACK;');
        //         throw $e;
        //     }

        //     /**
        //      * 按批更新
        //      */
        //     try {
        //         $batchSize = 100;
        //         foreach ($volumes as $volume) {
        //             $batches = array_chunk($volume['chapters'], $batchSize);
        //             foreach ($batches as $chapterBatch) {
        //                 $wpdb->query('START TRANSACTION;');

        //                 foreach ($chapterBatch as $chapter) {
        //                     if (!empty($chapter['title'])) {

        //                         [$chapterId, $similarity] = $originalContents->idBySimilarName('chapter', $chapter['title'], 2);

        //                         // 存在相似
        //                         if ($chapterId) {
        //                             $chapterData = [];

        //                             // 更新标题
        //                             if ($similarity > 0)
        //                                 $chapterData['post_title'] = $chapter['title'];
        //                             // 更新内容
        //                             if (!empty($chapter['content']))
        //                                 $chapterData['post_content'] = $chapter['content'];
        //                             // 更新爹
        //                             $originalParent = $originalContents->contentsItemById($chapterId)->post_parent;
        //                             $newParent = $volumeNameIdMap[$volume['title']];
        //                             if (!empty($newParent) && $newParent != $originalParent)
        //                                 $chapterData['post_parent'] = $newParent;

        //                             if ($chapterData) {
        //                                 $chapterData['ID'] = $chapterId;
        //                                 $chapterId = wp_update_post($chapterData, true);
        //                             }

        //                             // 否则，不需要做任何事
        //                         }
        //                         // 不存在：插入
        //                         else {
        //                             $chapterId = wp_insert_post([
        //                                 'post_title' => $chapter['title'],
        //                                 'post_content' => $chapter['content'] ?? '',
        //                                 'post_parent' => $volumeNameIdMap[$volume['title']],
        //                                 'post_author' => $authorId,
        //                                 'post_type' => BookPost::KBP_BOOK,
        //                                 'post_status' => 'publish',
        //                             ], true);
        //                         }
        //                         static::assertWpdbResult($chapterId);
        //                     }
        //                 }

        //                 $wpdb->query('COMMIT;');
        //             }
        //         }
        //     } catch (Exception $e) {
        //         $wpdb->query('ROLLBACK;');
        //         throw $e;
        //     }

        //     /**
        //      * 恢复原来设定
        //      */
        //     wp_defer_term_counting(false);
        //     wp_defer_comment_counting(false);

        //     remove_filter('pre_wp_unique_post_slug', $uniSlug);

        //     add_action('do_pings', 'do_all_pings', 10, 0);

        //     set_time_limit($originalTimeLimit);
        // }

        /**
         * 将整本书更新至指定状态
         * @param mixed $book 更新的状态
         * @param Book $original 当前的书籍
         * @param bool $forceIdempotent 操作是否严格遵循幂等律，如果是，必须明确给定每个项的id以检索唯一资源，这意味着不允许查找失败时插入
         * @return array report
         * @throws QueryException 数据交互时发生错误 
         * @throws Exception 
         */
        public static function putBook($book, Book $original, bool $forceIdempotent = false): array
        {
            // if (empty($book['id']) || $book['id'] != $original->ID)
            //     throw QueryException::fieldInvalid('book id invalid: bookId = ' . $book['id'] . ", target = " . $original->ID);

            // $report = [];
            // $functionWatch = new StopWatch();
            // $stepWatch = new StopWatch();
            $logger = new Logger();
            $logger->registerContext('steps');
            $logger->registerContext('summary');
            $logger->resetContexts();


            wp_defer_term_counting(true);
            wp_defer_comment_counting(true);

            // 截胡slug的查找，直接给定一个本机唯一id
            $uniSlug = fn ($override_slug) => $override_slug ?? uniqid();
            add_filter('pre_wp_unique_post_slug', $uniSlug);

            // 不知道什么鬼用的pingback
            remove_action('do_pings', 'do_all_pings', 10);

            //无限时间
            $originalTimeLimit = ini_get('max_execution_time');
            set_time_limit(9999);

            // 重要：关闭级联删除
            MetaManager::setBookautoCascadeDeletion(false);



            //书meta
            //卷meta
            //目录顺序

            global $wpdb;
            try {
                $wpdb->query('START TRANSACTION;');

                /**
                 * 更新book
                 * cover和author是只读的
                 */
                $authorId = $original->authorId;
                $bookMeta = [];

                // 题目
                if (!empty($book['title']) && $book['title'] != $original->title)
                    $bookMeta['post_title'] = $book['title'];

                // 简介
                if (!empty($book['excerpt']) && $book['excerpt'] != $original->excerpt)
                    $bookMeta['post_excerpt'] = $book['excerpt'];

                // 类别
                $oldGenres = array_map(fn (WP_Term $wp_term) => $wp_term->term_id, $original->genres);
                $newGenres = $book['genres'] ?? [];
                if (!ArrayHelper::arrayValuesEqual($oldGenres, $newGenres))
                    $bookMeta['tax_input'] = [BookPost::KBP_BOOK_GENRE => $newGenres];

                // 标签
                $oldTags = array_map(fn (WP_Term $wp_term) => $wp_term->name, $original->tags);
                $newTags = $book['tags'] ?? [];
                if (!ArrayHelper::arrayValuesEqual($oldTags, $newTags))
                    $bookMeta['tags_input'] = $newTags;

                if ($bookMeta) {
                    $bookMeta['ID'] = $original->ID;
                    $result = wp_update_post($bookMeta, true);
                    static::assertWpdbResult($result);

                    // print_r("\t\tupdated book: {$stepWatch->getElapsedStringAndReset()}\n");
                    // $report['steps'][] = "[book] updated: {$stepWatch->getElapsedStringAndReset()}";
                    $logger->addLog('steps', 'steps',  'book', 'updated');
                } else {
                    // $report['steps'][] = "[book] skipped: {$stepWatch->getElapsedStringAndReset()}";
                    $logger->addLog('steps', 'steps',  'book', 'skipped');
                }

                /**
                 * 更新volumes
                 */
                if (!empty($book['volumes'])) {

                    /** @var BookContents */
                    $originalContents = $original->contents;
                    $volumeCounter = 0;

                    foreach ($book['volumes'] as $key => $volume) {
                        // 更新（存在有效的id）
                        if (!empty($volume['id'])) {
                            if (!$originalContents->containsId($volume['id']))
                                throw QueryException::fieldInvalid("\"{$volume['id']}\" is not a existing volume ID");

                            $volumeId = $volume['id'];

                            $volumeMeta = [];
                            // 标题
                            if (!empty($volume['title']) && $originalContents->idByName('volume', $volume['title']) != $volumeId)
                                $volumeMeta['post_title'] = $volume['title'];

                            // 需要更新
                            if ($volumeMeta) {
                                $volumeMeta['ID'] = $volumeId;
                                $volumeId = wp_update_post($volumeMeta, true);
                                $volumeCounter++;
                            }
                        }
                        // 新增
                        else if (!$forceIdempotent) {
                            $volumeId = wp_insert_post([
                                'post_title' => $volume['title'],
                                'post_parent' => $original->ID,
                                'post_author' => $authorId,
                                'post_type' => BookPost::KBP_BOOK,
                                'post_status' => 'publish',
                            ], true);
                            $volumeCounter++;
                        } else {
                            throw QueryException::fieldInvalid("Missing volume id");
                        }
                        static::assertWpdbResult($volumeId);
                        $book['volumes'][$key]['id'] = $volumeId; //更新ID
                    }

                    // print_r("\t\tinserted / updated volumes: {$stepWatch->getElapsedStringAndReset()}\n");
                    // $report['steps'][] = "[volumes] $volumeCounter inserted / updated: {$stepWatch->getElapsedStringAndReset()}";
                    $logger->addLog('steps', 'steps',  'volumes', "$volumeCounter inserted / updated");


                    /**
                     * 删除不复存在的卷节点
                     */
                    $oldVolumeIds = array_map(fn ($v) => $v->ID, $originalContents->getVolumes());
                    $newVolumeIds = array_map(fn ($v) => $v['id'], $book['volumes']);
                    $deletes = array_diff($oldVolumeIds, $newVolumeIds);
                    foreach ($deletes as $deleteid)
                        wp_delete_post($deleteid);

                    // if (count($deletes) > 0)
                    // print_r("\t\tdeleted " . count($deletes) . " volumes: {$stepWatch->getElapsedStringAndReset()}\n");
                    // $report['steps'][] = "[volumes] " . count($deletes) . " deleted: {$stepWatch->getElapsedStringAndReset()}";
                    $logger->addLog('steps', 'steps',  'volumes', count($deletes) . ' deleted');


                    /**
                     * 更新章节
                     */
                    $batchSize = 100;
                    $chapterCounter = 0;
                    foreach ($book['volumes'] as $vkey => $volume) {
                        $batches = array_chunk($volume['chapters'], $batchSize);
                        foreach ($batches as $batchIndex => $chapterBatch) {
                            // $wpdb->query('START TRANSACTION;');

                            foreach ($chapterBatch as $ckey => $chapter) {
                                if (!empty($chapter['id'])) {
                                    if (!$originalContents->containsId($chapter['id']))
                                        throw QueryException::fieldInvalid("\"{$chapter['id']}\" is not a existing chapter ID");

                                    $chapterId = $chapter['id'];

                                    $chapterData = [];
                                    // 更新标题
                                    if (!empty($chapter['title']) && $originalContents->idByName('chapter', $chapter['title']) != $chapterId)
                                        $chapterData['post_title'] = $chapter['title'];

                                    // 更新内容
                                    if (!empty($chapter['content']))
                                        $chapterData['post_content'] = $chapter['content'];

                                    // 更新爹
                                    $originalParent = $originalContents->contentsItemById($chapterId)->post_parent;
                                    $newParent = $volume['id'];
                                    if (!empty($newParent) && $newParent != $originalParent)
                                        $chapterData['post_parent'] = $newParent;

                                    if ($chapterData) {
                                        $chapterData['ID'] = $chapterId;
                                        $chapterId = wp_update_post($chapterData, true);
                                        $chapterCounter++;
                                    }
                                }
                                // 不存在：插入
                                else if (!$forceIdempotent) {
                                    $chapterId = wp_insert_post([
                                        'post_title' => $chapter['title'],
                                        'post_content' => $chapter['content'] ?? '',
                                        'post_parent' => $volume['id'],
                                        'post_author' => $authorId,
                                        'post_type' => BookPost::KBP_BOOK,
                                        'post_status' => 'publish',
                                    ], true);
                                    $chapterCounter++;
                                } else {
                                    throw QueryException::fieldInvalid("Missing chapter id");
                                }
                                static::assertWpdbResult($chapterId);
                                $book['volumes'][$vkey]['chapters'][$batchSize * $batchIndex + $ckey]['id'] = $chapterId; //更新ID
                            }

                            // $wpdb->query('COMMIT;');
                        }
                    }

                    // print_r("\t\tinserted / updated chapters: {$stepWatch->getElapsedStringAndReset()}\n");
                    // $report['steps'][] = "[chapters] $chapterCounter inserted / updated: {$stepWatch->getElapsedStringAndReset()}";
                    $logger->addLog('steps', 'steps',  'chapters', "$chapterCounter inserted / updated");



                    /**
                     * 删除完全没用到的旧章节
                     */

                    $oldChapterIds = [];
                    $newChapterIds = [];
                    foreach ($originalContents->getVolumes() as $contentsVolume)
                        $oldChapterIds = array_merge($oldChapterIds, array_map(fn ($c) => $c->ID, $originalContents[$contentsVolume->ID]));
                    foreach ($book['volumes'] as $volume)
                        $newChapterIds = array_merge($newChapterIds, array_map(fn ($c) => $c['id'], $volume['chapters']));

                    $deletes = array_diff($oldChapterIds, $newChapterIds);
                    foreach ($deletes as $deleteid)
                        wp_delete_post($deleteid);

                    // if (count($deletes) > 0)
                    // print_r("\t\tdeleted " . count($deletes) . " chapters: {$stepWatch->getElapsedStringAndReset()}\n");
                    // $report['steps'][] = "[chapters] " . count($deletes) . " deleted: {$stepWatch->getElapsedStringAndReset()}";
                    $logger->addLog('steps', 'steps',  'chapters', count($deletes) . ' deleted');



                    /**
                     * 更新顺序
                     */
                    /**
                     * 检测顺序是否需要更新
                     * 双指针法：
                     * 0. 只检测id变更
                     * 1. 只有删除和末尾新增不会影响顺序
                     * 2. 指针同时指向旧表、新表
                     *   0. 若新表元素不存在于旧表，返回需要更新
                     *   1. 若匹配，同时前进一步
                     *   2. 若不匹配，检测新元素是否处于旧表中
                     *      1. 不是：退出循环，看看从此以后是不是均为递增顺序（顺应排序逻辑）
                     *      2. 是：旧表前进一步，代表删除了旧元素
                     * 3. 倒最终，如果新表顺利遍历结束，则说明无须更新顺序
                     */
                    function needUpdateOrder($old, $new): bool
                    {
                        /**
                         * test cases:
                         * $old1 = [1,2,3]; $new1 = [1,2,3,4]; // false
                         * $old2 = [1,2,3,4]; $new2 = [1,2,3]; // false
                         * $old3 = [1,2,3]; $new3 = [3,2,1]; // true
                         * $old4 = []; $new4 = [1,2,3]; // false
                         * $old5 = [1,2,3]; $new5 = []; // false
                         * $old6 = [2,1,3]; $new6 = [1,2,3]; // true
                         * $old7 = [1,2]; $new7 = [2,1]; //true
                         * $old8 = [2,1]; $new8 = [1,2]; //true
                         * $old9 = [2]; $new9 = [2,1]; //true
                         * $old10 = [2]; $new10 = [2,3]; //false
                         * $old11 = []; $new11 = [1]; //false
                         * $old12 = [2]; $new12 = [1]; //false
                         */
                        if (empty($old) || empty($new))
                            return false;
                        $i = 0;
                        $j = 0;
                        while ($i < count($old) && $j < count($new)) {
                            // 相等，同时推进
                            if ($old[$i] == $new[$j]) {
                                $i++;
                                $j++;
                            }
                            // 不相等，立即检测是否为末尾递增
                            else if (array_search($new[$j], $old) === false) break;
                            // 视为删除
                            else $i++;
                        }

                        // 能否推进到最后？？？
                        for ($j = max($j, 1); $j < count($new); $j++)
                            if (array_search($new[$j], $old) !== false || $new[$j - 1] > $new[$j])
                                return true;

                        return false;
                    }

                    $needUpdate = false;
                    if (needUpdateOrder($oldVolumeIds, $newVolumeIds))
                        $needUpdate = true;
                    else {
                        foreach ($book['volumes'] as $volume) {

                            $oldChapterIds = [];
                            $newChapterIds = [];

                            // 如果新volume存在于旧volume，获取旧数据
                            if (!empty($originalContents[$volume['id']]))
                                $oldChapterIds = array_map(fn ($c) => $c->ID, $originalContents[$volume['id']]);
                            // 如果新volume确实存在章节，获取新数据
                            if (!empty($volume['chapters']))
                                $newChapterIds = array_map(fn ($c) => $c['id'], $volume['chapters']);

                            // print_r($oldChapterIds);
                            // print_r($newChapterIds);
                            // print_r("\n");
                            if (needUpdateOrder($oldChapterIds, $newChapterIds)) {
                                $needUpdate = true;
                                break;
                            }
                        }
                    }

                    if ($needUpdate) {
                        static::updateBookHierarchy($book, false);
                        // print_r($book);
                        // print_r("\t\tupdated menu order: {$stepWatch->getElapsedStringAndReset()}\n");
                        // $report['steps'][] = "[menu order] updated: {$stepWatch->getElapsedStringAndReset()}";
                        $logger->addLog('steps', 'steps',  'menu order', 'updated');
                    } else
                        // $report['steps'][] = "[menu order] skipped: {$stepWatch->getElapsedStringAndReset()}";
                        $logger->addLog('steps', 'steps',  'menu order', 'skipped');
                } else {
                    $logger->addLog('steps', 'steps',  'volumes', 'skipped');
                }


                $wpdb->query('COMMIT;');
                // $report['steps'][] = "[commit] all changes: {$stepWatch->getElapsedStringAndReset()}";
                $logger->addLog('steps', 'steps',  'commit', 'all changes');
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK;');
                throw $e;
            } finally {
                /**
                 * 恢复原来设定
                 */
                wp_defer_term_counting(false);
                wp_defer_comment_counting(false);

                remove_filter('pre_wp_unique_post_slug', $uniSlug);

                add_action('do_pings', 'do_all_pings', 10, 0);

                set_time_limit($originalTimeLimit);

                MetaManager::setBookautoCascadeDeletion(true);
            }

            // print_r("put book function executing time: {$functionWatch->getElapsedStringAndReset()}\n");
            // $report['summary'][] = "[function] put book executing time: {$functionWatch->getElapsedStringAndReset()}";
            $logger->addLog('summary', 'summary',  'function', 'completed');

            // return $report;
            return $logger->getLog();
        }

        /**
         * 删除书本数据类型时，会级联删除所有书本
         * todo: 删除会把文章先查出来，有点傻逼
         * 删除一本书可能涉及几十万字、几十mb资源的文字加载，但我为什么浪费这些资源？
         * 我必须把内容字段从查询结果中剔除：
         * https://stackoverflow.com/questions/4778421/is-it-possible-to-have-get-posts-or-wp-query-not-return-the-post-content
         * @param Book|WP_Post|int $book 
         * @return WP_Post|false|null 成功删除时返回父文章，失败时返回false或null，正在级联删除、非book不处理返回null
         * @throws QueryException 
         */
        public static function deleteBookPart(Book|WP_Post|int $book): WP_Post|false|null
        {
            // if (static::$cascadeDeleting || !static::$cascadeDeleteEnabled)
            //     return null;
            // throw new Exception();

            if (!$book instanceof WP_Post)
                $book = get_post($book);

            // 检查是否为文章类型为 'book' 的文章
            if ($book->post_type === BookPost::KBP_BOOK) {

                // try {
                //     // 设置递归标识
                //     // static::$cascadeDeleting = true;
                //     // $deleteWatch = new StopWatch();


                //     // global $wpdb;
                //     // $wpdb->query('START TRANSACTION;');

                //     // 手动操办它的仪式


                //     // $wpdb->query('COMMIT;');
                //     return $book;
                // } catch (Exception $e) {
                //     error_log($e->getMessage());
                //     // $wpdb->query('ROLLBACK;');
                //     return false;
                // } finally {
                //     // 重置递归标识
                //     // static::$cascadeDeleting = false;
                //     // error_log("cascade delete execution time: {$deleteWatch->getElapsedStringAndReset()}");
                // }
                static::$recursionDepth = 1000;
                static::cascadeDeleteBody($book);
                return $book;
            }

            return null;
        }

        /**
         * 级联删除改文章的所有相同类型子文章
         * @param WP_Post $post 删除的文章
         * @return void 
         * @throws QueryException 
         */
        private static function cascadeDeleteBody(WP_Post $post)
        {
            if (static::$recursionDepth < 0)
                throw new Exception();
            else
                static::$recursionDepth -= 1;
            // 获取子文章列表
            $child_posts = get_children([
                'post_parent' => $post->ID,
                'post_type'   => $post->post_type,
            ]);

            // error_log("$post->ID: " . implode(", ", array_map(fn ($item) => $item->ID, $child_posts)) . "\n");

            // 循环删除子文章
            foreach ($child_posts as $child_post) {
                // 递归删除子文章
                static::cascadeDeleteBody($child_post);
            }

            // if ($post instanceof WP_Post)
            //     error_log("deleting: $post->ID");
            // else
            //     error_log("deleting: $post");

            // 删除父文章
            $result = wp_delete_post($post->ID, true);
            static::assertWpdbResult($result);

            // if ($result instanceof WP_Post)
            //     error_log("deleted: $result->ID");
            // else
            //     error_log("deleted: $result");
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
         * TODO: 允许只更新某卷的章，或只更新卷
         * @param array $bookHierarchy 
         * @param bool $sanitize 是否检测数据有效性：key均为数字、均来自本书
         * @return int 
         * @throws Exception 
         */
        public static function updateBookHierarchy(array $bookHierarchy, bool $sanitize = true): int
        {
            // print_r("book hierarchy: ");
            // print_r($bookHierarchy);
            // print_r("\n");
            /**
             * 确保：
             * 1. 所有id均存在，且为数字，清除id不符合的项
             * 2. book至少有1个volume，
             * 3. volume可以不包含chapter
             */
            if (!array_key_exists('id', $bookHierarchy) || !is_numeric($bookHierarchy['id']) || empty($bookHierarchy['volumes']))
                return 0;


            if ($sanitize) {
                // 清除无效volume

                foreach ($bookHierarchy['volumes'] as $vkey => &$volume) {
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
                unset($volume);
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

            if ($sanitize) {
                // 确定项来自这本书
                // 当作set用
                $bookPostIds = array_flip(array_map(fn ($item) => $item->ID, static::bookHierarchy($bookId, null)));
                foreach ($idPairs as $id => $order)
                    if (!array_key_exists($id, $bookPostIds))
                        throw QueryException::fieldInvalid();
            }


            // $idPairs = array_filter($idPairs, fn ($id) => array_key_exists($id, $bookPostIds), ARRAY_FILTER_USE_KEY);
            if (count($idPairs) === 0)
                return 0;

            // 压成负数（因为新建文章默认序列为0，应该是最新章节）
            $max = max($idPairs) + 1;
            foreach ($idPairs as &$idPair)
                $idPair -= $max;

            // print_r($idPairs);
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
