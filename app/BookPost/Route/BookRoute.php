<?php

namespace KarsonJo\BookPost\Route {

    use Exception;
    use KarsonJo\BookPost\Book;
    use KarsonJo\BookPost\BookContents;
    use KarsonJo\BookPost\BookContentsItem;
    use KarsonJo\BookPost\Importer\Importer;
    use WP_REST_Response;

    use KarsonJo\BookPost\SqlQuery as Query;
    use KarsonJo\BookPost\SqlQuery\AuthorQuery;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use KarsonJo\BookPost\SqlQuery\QueryException;
    use KarsonJo\Utilities\Algorithms\StringAlgorithms;
    use NovelCabinet\Utilities\ArrayHelper;
    use Symfony\Component\Mime\Message;
    use TenQuality\WP\Database\QueryBuilder;
    use WP_REST_Request;
    use WP_Term;

    class BookRoute
    {
        const FORCE_CREATE_BOOK_QUERY_KEY = "force";
        public static function init($apiDomain = 'kbp', $apiVersion = 'v1')
        {
            $namespace = $apiDomain . '/' . $apiVersion;

            add_action('rest_api_init', function () use ($namespace) {
                static::allowLocalHttpAuth();

                static::bookRepresentation($namespace);
            });
        }

        /**
         * abstract REST API representation: 
         * book
         * 只有用户拥有import权限才可以调用
         * @param mixed $namespace 
         * @param string $path 
         * @return void 
         */
        static function bookRepresentation($namespace, $path = '/books')
        {
            /**
             * 插入book
             */
            register_rest_route($namespace, $path, [
                'methods' => 'POST',
                'permission_callback' => fn () => current_user_can('import'),
                'callback' => function (WP_REST_Request $request) use ($namespace, $path) {
                    $queryVars = $request->get_query_params();
                    $bookJson = $request->get_json_params();
                    /**
                     * 检测是否有title
                     */
                    if (empty($bookJson['title'])) {
                        $e = QueryException::fieldInvalid('book title empty');
                        return new WP_REST_Response([
                            'message' => 'please always provide a book title',
                            'error' => [
                                'code' => $e->getCode(),
                                'message' => $e->getMessage(),
                            ]
                        ], 422);
                    }

                    /**
                     * 创建书籍：书籍不存在，或者有强制创建字段
                     */
                    if (
                        isset($queryVars[static::FORCE_CREATE_BOOK_QUERY_KEY]) && filter_var($queryVars[static::FORCE_CREATE_BOOK_QUERY_KEY], FILTER_VALIDATE_BOOLEAN)
                        || BookQuery::bookSimilarMatch($bookJson['title'], $bookJson['author'], 0, 0) === null
                    ) {
                        // 给定的数据是否存在至少一个卷
                        if (empty($bookJson['volumes'])) {
                            $e = QueryException::fieldInvalid('book volumes empty');
                            return new WP_REST_Response([
                                'message' => 'cannot create book due to missing / empty field: volumes',
                                'error' => [
                                    'code' => $e->getCode(),
                                    'message' => $e->getMessage(),
                                ]
                            ], 422);
                        }

                        // 是时候创建一本新书了
                        try {
                            [$bookId, $report] = BookQuery::createBook($bookJson);
                            return new WP_REST_Response(
                                [
                                    'message' => 'successfully created',
                                    'report' => $report,
                                    'data' => static::bookToOutputResult(BookQuery::getBook($bookId))
                                ],
                                201,
                                ['Location' => rest_url("$namespace/$path/$bookId")]
                            );
                        } catch (Exception $e) {
                            return new WP_REST_Response([
                                'message' => 'failed due to insert error',
                                'error' => [
                                    'code' => $e->getCode(),
                                    'message' => $e->getMessage(),
                                ]
                            ], 422);
                        }
                    }
                    // 书籍存在：报错
                    else {
                        $e = QueryException::fieldInvalid('book already exists');
                        return new WP_REST_Response([
                            'message' => 'failed due to duplicate name and author, you may set query param [?' . static::FORCE_CREATE_BOOK_QUERY_KEY . '=true] to create a new book anyway',
                            'error' => [
                                'code' => $e->getCode(),
                                'message' => $e->getMessage(),
                            ]
                        ], 409);
                    }
                }
            ]);

            /**
             * 在书堆中检索
             */
            register_rest_route($namespace, $path,  [
                'methods' => 'GET',
                'permission_callback' => fn () => current_user_can('import'),
                'callback' => function (WP_REST_Request $request) {
                    /**
                     * book match
                     */
                    if (isset($request['title']) || isset($request['author'])) {
                        $book = BookQuery::bookSimilarMatch($request['title'], $request['author'] ?? null);
                        if ($book)
                            return new WP_REST_Response([static::bookToOutputResult($book)]);
                        else
                            return new WP_REST_Response([]);
                    }
                }
            ]);
            /**
             * 获取一本书
             */
            register_rest_route($namespace, $path . '/(?P<bookId>\d+)', [
                'methods' => 'GET',
                'permission_callback' => fn () => current_user_can('import'),
                'callback' => function (WP_REST_Request $request) {
                    $book = Book::initBookFromPost($request['bookId']);

                    if ($book)
                        return new WP_REST_Response(static::bookToOutputResult($book));

                    return APIRoute::response(null, "not found", 404);
                }
            ]);

            /**
             * 更新book
             */
            register_rest_route($namespace, $path . '/(?P<bookId>\d+)', [
                'methods' => 'PUT',
                'permission_callback' => fn () => current_user_can('import'),
                'callback' => function (WP_REST_Request $request) {
                    $jsonData = $request->get_json_params();

                    // 路由路径ID为空
                    if (empty($request['bookId'])) {
                        return APIRoute::response(null, "book id empty", 422);
                    }
                    // 数据中提供了ID，但与路由路径ID不符
                    else if (!empty($jsonData['id']) && $jsonData['id'] != $request['bookId']) {
                        return APIRoute::response(null, "book id provided but not matching url endpoint", 422);
                    }

                    $originalBook = BookQuery::getBook($request['bookId']);
                    if (!$originalBook)
                        return APIRoute::response(null, "book id invalid", 422);


                    try {
                        $report = BookQuery::putBook($request->get_json_params(), $originalBook);
                        return new WP_REST_Response([
                            'message' => 'successfully updated',
                            'report' => $report,
                        ]);
                    } catch (Exception $e) {
                        return new WP_REST_Response([
                            'message' => 'failed due to insert error',
                            'error' => [
                                'code' => $e->getCode(),
                                'message' => $e->getMessage(),
                            ]
                        ], 422);
                    }
                }
            ]);
        }

        /**
         * 允许本地环回地址通过http验证身份
         * @return void 
         */
        static function allowLocalHttpAuth()
        {
            add_filter('wp_is_application_passwords_available', function (bool $available) {
                return $available || $_SERVER['REMOTE_ADDR'] === '127.0.0.1';
            });
        }

        protected static function returnImport($tasks, $status = 200)
        {
            return new WP_REST_Response([
                'tasks' => $tasks,
            ], $status);
        }

        /**
         * 将book转换为输出格式（我也不确定应该写在哪）
         * @param Book $book 
         * @return array 
         */
        protected static function bookToOutputResult(Book $book): array
        {
            $bookInfo = [
                'title' => $book->title,
                'excerpt' => $book->excerpt,
                'author' => [
                    'id' => $book->authorId,
                    'login' => $book->authorLogin,
                    'name' => $book->author,
                ],
                'genres' => array_map(fn (WP_Term $term) => $term->term_id, $book->genres),
                // 'genres' => array_map(fn (WP_Term $term) => ['id' => $term->term_id, 'name' => $term->name], $book->genres),
                'tags' => $book->tags,
            ];

            return array_merge($bookInfo, (new BookContents($book->ID, false))->toJsonArray(['id', 'title']));
        }


        protected static function bookSimilarMatch(string $keywordTitle, ?string $keywordAuthor = null, $titleThreshold = 2, $authorThreshold = 2): ?Book
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
            if ($keywordAuthor) {
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
    }
}
