<?php

namespace KarsonJo\BookPost\Route {

    use Exception;
    use KarsonJo\BookPost\Book;
    use KarsonJo\BookPost\BookContents;

    use WP_REST_Response;


    use KarsonJo\BookPost\SqlQuery\AuthorQuery;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use KarsonJo\BookPost\SqlQuery\QueryException;
    use KarsonJo\Utilities\Algorithms\StringAlgorithms;
    use NovelCabinet\Utilities\ArrayHelper;
    use Throwable;
    use WP_Error;
    use WP_REST_Request;
    use WP_Term;

    class BookRestResource extends RestResource
    {
        const FORCE_CREATE_BOOK_QUERY_KEY = "force";
        protected static string $idPattern = '(?P<bookId>\d+)';

        protected function registerRoutes($namespace, $path, $permissions)
        {
            // print_r("route: $path\n");
            // print_r("route: " . static::pathWithIdPattern($path) . "\n");

            /**
             * 插入book
             */
            register_rest_route($namespace, $path, [
                'methods' => 'POST',
                'permission_callback' => static::permissionCallback($permissions),
                'callback' => fn ($r) => $this->insertBook($r, $namespace, $path)
            ]);

            /**
             * 在书堆中检索
             */
            register_rest_route($namespace, $path,  [
                'methods' => ['GET', 'HEAD'],
                'permission_callback' => static::permissionCallback($permissions),
                'callback' => fn ($r) => $this->searchBook($r)
            ]);

            /**
             * 获取一本书
             */
            register_rest_route($namespace, static::pathWithIdPattern($path), [
                'methods' => 'GET',
                'permission_callback' => static::permissionCallback($permissions),
                'callback' => fn ($r) => $this->getBook($r)
            ]);

            /**
             * 更新book
             */
            register_rest_route($namespace, static::pathWithIdPattern($path), [
                'methods' => 'PUT',
                'permission_callback' => static::permissionCallback($permissions),
                'callback' => fn ($r) => $this->putBook($r)
            ]);

            register_rest_route($namespace, static::pathWithIdPattern($path), [
                'methods' => 'DELETE',
                'permission_callback' => static::permissionCallback($permissions),
                'callback' => fn ($r) => $this->deleteBook($r)
            ]);
        }

        protected function getIdentifier()
        {
            return "Book";
        }


        protected function insertBook(WP_REST_Request $request, string $namespace, string $path)
        {
            $queryVars = $request->get_query_params();
            $bookJson = $request->get_json_params();
            /**
             * 检测是否有title
             */
            if (empty($bookJson['title'])) {
                return new WP_REST_Response([
                    'message' => 'please always provide a book title',
                    'error' => static::getErrorMessage(QueryException::fieldInvalid('book title empty')),
                ], 422);
            }

            /**
             * 创建书籍：书籍不存在，或者有强制创建字段
             */
            if (
                isset($queryVars[static::FORCE_CREATE_BOOK_QUERY_KEY]) && filter_var($queryVars[static::FORCE_CREATE_BOOK_QUERY_KEY], FILTER_VALIDATE_BOOLEAN)
                || BookQuery::bookSimilarMatch($bookJson['title'], $bookJson['author']['name'], 0, 0) === null
            ) {
                // 给定的数据是否有卷节点
                if (!isset($bookJson['volumes'])) {
                    return new WP_REST_Response([
                        'message' => 'cannot create book due to missing field: volumes',
                        'error' => static::getErrorMessage(QueryException::fieldInvalid('book volumes empty'))
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
                } catch (Throwable $e) {
                    return new WP_REST_Response([
                        'message' => 'failed due to insert error',
                        'error' => static::getErrorMessage($e)
                    ], 422);
                }
            }
            // 书籍存在：报错
            else {
                return new WP_REST_Response([
                    'message' => 'failed due to duplicate name and author, you may set query param [?' . static::FORCE_CREATE_BOOK_QUERY_KEY . '=true] to create a new book anyway',
                    'error' => static::getErrorMessage(QueryException::fieldInvalid('book already exists'))
                ], 409);
            }
        }

        protected function searchBook(WP_REST_Request $request)
        {
            $method = $request->get_method();
            if ($method === 'HEAD')
                return new WP_REST_Response(null, 200);
            /**
             * book match
             */
            if (isset($request['title']) || isset($request['author']['name'])) {
                $book = BookQuery::bookSimilarMatch($request['title'], $request['author']['name'] ?? null, 0, 0);
                if ($book)
                    return new WP_REST_Response([static::bookToOutputResult($book)]);
                else
                    return new WP_REST_Response([]);
            }
        }

        protected function getBook(WP_REST_Request $request)
        {
            $book = Book::initBookFromPost($request['bookId']);

            if ($book)
                return new WP_REST_Response(static::bookToOutputResult($book));

            return APIRoute::response(null, "not found", 404);
        }

        protected function putBook(WP_REST_Request $request)
        {
            $jsonData = $request->get_json_params();

            // 路由路径ID为空
            // if (empty($request['bookId'])) {
            //     return APIRoute::response(null, "book id empty", 422);
            // }
            // 数据中提供了ID，但与路由路径ID不符
            if (!empty($jsonData['id']) && $jsonData['id'] != $request['bookId']) {
                return APIRoute::response(null, "book id provided but not matching url endpoint", 422);
            }

            $originalBook = BookQuery::getBook($request['bookId']);
            if (!$originalBook)
                return APIRoute::response(null, "book id invalid", 404);


            try {
                $report = BookQuery::putBook($request->get_json_params(), $originalBook);
                return new WP_REST_Response([
                    'message' => 'successfully updated',
                    'report' => $report,
                ]);
            } catch (Exception $e) {
                return new WP_REST_Response([
                    'message' => 'failed due to insert error',
                    'error' => static::getErrorMessage($e)
                ], 422);
            }
        }

        protected function deleteBook(WP_REST_Request $request)
        {
            
            $result = BookQuery::deleteBook(intval($request['bookId']));
            if ($result)
                return new WP_REST_Response(['message' => __('successfully-deleted-msg', 'NovelCabinet')]);
            else if ($result === null)
                return new WP_REST_Response(['message' => __('delete-failed-msg', 'NovelCabinet')], 404);
            else
                return new WP_REST_Response(['message' => __('delete-failed-msg', 'NovelCabinet')], 422);
        }



        // public static function init($apiDomain = 'kbp', $apiVersion = 'v1')
        // {
        //     $namespace = $apiDomain . '/' . $apiVersion;

        //     add_action('rest_api_init', function () use ($namespace) {
        //         // print_r(123);
        //         // static::allowLocalHttpAuth();

        //         static::bookCoverRepresentation($namespace);
        //     });
        // }


        static function bookCoverRepresentation($namespace, $path = '/books/(?P<bookId>\d+)/cover')
        {
            register_rest_route($namespace, $path, [
                'methods' => 'GET',
                'permission_callback' => fn () => current_user_can('import'),
                'callback' => function (WP_REST_Request $request) {
                    $book = BookQuery::getBook($request['bookId']);
                    if (!$book || $book->ID != $request['bookId']) {
                        return new WP_REST_Response([
                            'message' => 'book not exists',
                            'error' => static::getErrorMessage(QueryException::fieldInvalid(('book not found')))
                        ], 404);
                    }

                    $url = get_the_post_thumbnail_url($book->ID);
                    if (!$url)
                        return new WP_REST_Response([
                            'message' => 'thumbnail not exists',
                            'error' => static::getErrorMessage(QueryException::fieldInvalid(('thumbnail not found')))
                        ], 404);
                    return new WP_REST_Response(['url' => $url]);
                }
            ]);
            /**
             * php的put/patch请求不接受multipart/form-data
             * 但restful api在上传文件时需要put的multipart支持
             * 因此自行处理
             * https://bugs.php.net/bug.php?id=55815
             * 
             */
            register_rest_route($namespace, $path, [
                'methods' => 'POST',
                'permission_callback' => fn () => current_user_can('import'),
                'callback' => function (WP_REST_Request $request) {

                    /**
                     * 检测输入合法性
                     */
                    $book = BookQuery::getBook($request['bookId']);
                    if (!$book || $book->ID != $request['bookId']) {
                        return new WP_REST_Response([
                            'message' => 'failed uploading image',
                            'error' => static::getErrorMessage(QueryException::fieldInvalid(('book not found')))
                        ], 404);
                    }
                    if (empty($_FILES['src'])) {
                        return new WP_REST_Response([
                            'message' => 'failed uploading image',
                            'error' => static::getErrorMessage(QueryException::fieldInvalid(('src is empty')))
                        ], 422);
                    }


                    /**
                     * 检测当前封面图状态
                     */
                    $deleteOld = false;
                    //有封面图
                    if (($oldThumbnailId = get_post_thumbnail_id($book->ID)) && ($thumbnailFile = get_attached_file($oldThumbnailId))) {
                        $newMd5Hash = md5_file($_FILES["src"]["tmp_name"]);
                        $currMd5Hash = md5_file($thumbnailFile);
                        // 文件相同
                        if ($newMd5Hash == $currMd5Hash)
                            return new WP_REST_Response(['message' => 'current cover is the same'], 200);
                        // 删除旧的
                        else
                            $deleteOld = true;
                    }


                    /**
                     * 上传新图片
                     */
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');

                    // 不要产生不同尺寸
                    // todo: 也许应该上升为设置
                    $forceOnlyOneSize = fn () => [];
                    add_filter('intermediate_image_sizes_advanced', $forceOnlyOneSize, 9, 0);

                    $attachment_id = media_handle_upload('src', $book->ID);
                    if ($attachment_id instanceof WP_Error) {
                        return new WP_REST_Response([
                            'message' => 'failed uploading image',
                            'error' => static::getErrorMessage($attachment_id),
                        ], 422);
                    }

                    $result = set_post_thumbnail($book->ID, $attachment_id);
                    if (!$result) {
                        return new WP_REST_Response([
                            'message' => 'file uploaded, but failed to set thumbnail',
                            'error' => static::getErrorMessage(['unknown']),
                        ], 422);
                    }

                    remove_filter('intermediate_image_sizes_advanced', $forceOnlyOneSize);


                    /**
                     * 秋后算账（删除）
                     */
                    if ($oldThumbnailId && $deleteOld) {
                        $attachement = get_post($oldThumbnailId);
                        // 是从属于该文章的attachment才删除
                        if ($attachement->post_parent == $book->ID)
                            wp_delete_attachment($oldThumbnailId, true);
                    }

                    if ($oldThumbnailId)
                        return new WP_REST_Response(['message' => 'cover successfully updated']);
                    else
                        return new WP_REST_Response(['message' => 'cover successfully uploaded'], 201);
                }
            ]);
        }




        /**
         * 允许本地环回地址通过http验证身份
         * @return void 
         */
        // static function allowLocalHttpAuth()
        // {
        //     print_r(1);
        //     add_filter('wp_is_application_passwords_available', function (bool $available) {
        //         print_r($_SERVER['REMOTE_ADDR']);
        //         return $available || $_SERVER['REMOTE_ADDR'] === '127.0.0.1';
        //     });
        // }

        /**
         * 将book转换为输出格式（我也不确定应该写在哪）
         * @param Book $book 
         * @return array 
         */
        protected static function bookToOutputResult(?Book $book): array
        {
            if ($book == null)
                return [];
            $bookInfo = [
                'id' => $book->ID,
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


        // protected static function bookSimilarMatch(string $keywordTitle, ?string $keywordAuthor = null, $titleThreshold = 2, $authorThreshold = 2): ?Book
        // {
        //     // 必须指定title
        //     if (!$keywordTitle)
        //         return null;

        //     /**
        //      * 精准匹配title，模糊匹配author
        //      */
        //     $books = BookQuery::getBooks(['post_title' => $keywordTitle]);

        //     if (count($books) > 0) {
        //         /**
        //          * 书名有匹配，一定会返回一本
        //          * 如果有多本，返回作者名最相似的
        //          * 如果没给定作者名，那摆烂返回第一本
        //          */
        //         if (!$keywordAuthor)
        //             return $books[0];

        //         [$book, $value] = ArrayHelper::minBy(
        //             $books,
        //             fn (Book $book) => StringAlgorithms::levenshteinWithThreshold($book->authorLogin, $keywordAuthor, $authorThreshold, PHP_INT_MAX),
        //             0
        //         );

        //         return $book;
        //     }

        //     /**
        //      * 精准匹配author，模糊匹配title
        //      */
        //     if ($keywordAuthor) {
        //         $authorId = AuthorQuery::getAuthorID($keywordAuthor);
        //         if ($authorId) {
        //             $books = BookQuery::getBooks(['post_author' => $authorId]);
        //             if (count($books) > 0) {
        //                 /**
        //                  * 作者名有匹配，不一定就返回书本
        //                  * 返回书名误差最小，且在阈值之内的一本
        //                  */
        //                 [$book, $value] = ArrayHelper::minBy(
        //                     $books,
        //                     fn (Book $book) => StringAlgorithms::levenshteinWithThreshold($book->title, $keywordTitle, $titleThreshold, PHP_INT_MAX),
        //                     0
        //                 );

        //                 if ($value <= $titleThreshold)
        //                     return $book;
        //             }
        //         }
        //     }

        //     return null;
        // }

        // protected static function getErrorMessage(WP_Error|Exception|array $error): array
        // {
        //     if ($error instanceof WP_Error)
        //         return ['code' => $error->get_error_code(), 'message' => $error->get_error_message()];
        //     if ($error instanceof Exception)
        //         return ['code' => $error->getCode(), 'message' => $error->getMessage()];
        //     return ['code' => $error[0] ?? '', 'message' => $error[1] ?? ''];
        // }
    }
}
