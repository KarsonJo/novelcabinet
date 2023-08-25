<?php

namespace KarsonJo\BookPost\Route {

    use Exception;
    use KarsonJo\BookPost\BookContents;
    use WP_REST_Response;

    use KarsonJo\BookPost\SqlQuery as Query;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use Symfony\Component\Mime\Message;
    use TenQuality\WP\Database\QueryBuilder;

    class APIRoute
    {
        public static function init($apiDomain = 'kbp', $apiVersion = 'v1')
        {
            $namespace = $apiDomain . '/' . $apiVersion;

            add_action('rest_api_init', function () use ($namespace) {
                static::bookRating($namespace);
                static::createFavoriteList($namespace);
                static::updatePostFavorite($namespace);
                static::getBookContentsJson($namespace);

                static::bookRepresentation($namespace);
            });
        }

        static function bookRating($namespace, $path = '/rate/(?P<postId>\d+)')
        {
            register_rest_route($namespace, $path, array(
                'methods' => 'POST',
                'permission_callback' => '__RETURN_TRUE',
                'callback' => function ($request) {
                    $post_id = $request['postId'];
                    $rating = $request['rating'];
                    $user = wp_get_current_user();

                    if (!$user->ID)
                        return new WP_REST_Response(['message' => '需要登录才能评分喔'], 401);
                    // return new \WP_Error('invalid_user', '需要登录才能评分喔', array('status' => 401));

                    try {
                        BookQuery::setBookRating($post_id, $user, $rating);
                    } catch (Exception) {
                        return new WP_REST_Response(['message' => '已经评分过了'], 400);
                        // return new \WP_Error('rating_failed', '已经评分过了', array('status' => 400));
                    }

                    return new WP_REST_Response([
                        'message' => '评分成功',
                        'id' => $post_id,
                        'userRating' => $rating,
                        'avgRating' => BookQuery::getBookRating($post_id),
                    ]);
                }
            ));
        }

        static function createFavoriteList($namespace, $path = '/fav/create')
        {
            register_rest_route($namespace, $path, array(
                'methods' => 'POST',
                'permission_callback' => '__RETURN_TRUE',
                'callback' => function ($request) {
                    $user = wp_get_current_user();
                    $title = $request['title'];
                    $visibility = $request['visibility'];

                    if (!$user->ID)
                        return new WP_REST_Response(['message' => '需要登录才能收藏喔'], 401);

                    try {
                        $id = BookQuery::createUserFavoriteList($user, $title, $visibility);
                    } catch (Exception $e) {
                        return new WP_REST_Response(['title' => '创建收藏夹失败', 'message' => $e->getMessage()], 400);
                    }
                    return new WP_REST_Response([
                        'title' => '成功创建收藏夹',
                        'message' => "“{$title}”已成功创建",
                        'listId' => $id,
                        'listTitle' => $title
                    ]);
                }
            ));
        }

        /**
         * 更新用户对某篇文章收藏情况
         * @return void 
         */
        static function updatePostFavorite($namespace, $path = '/post-fav/update/(?P<postId>\d+)')
        {
            register_rest_route($namespace, $path, [
                'methods' => 'POST',
                'permission_callback' => '__RETURN_TRUE',
                'callback' => function ($request) {
                    $user = wp_get_current_user();
                    $post_id = $request['postId'];
                    $fav_lists = $request['favLists'];

                    if (!$user->ID)
                        return new WP_REST_Response(['message' => '需要登录才能收藏喔'], 401);

                    try {
                        BookQuery::updateUserPostFavorite($post_id, $user, $fav_lists);
                    } catch (Exception $e) {
                        return new WP_REST_Response(['title' => '收藏失败', 'message' => $e->getMessage()], 400);
                    }
                    return new WP_REST_Response(['title' => '收藏成功', 'message' => "文章收藏状态已更新"]);
                }
            ]);
        }

        /**
         * 目录JSON representation
         * @param mixed $namespace 
         * @param string $path 
         * @return void 
         */
        static function getBookContentsJson($namespace, $path = '/contents/(?P<postId>\d+)')
        {
            /**
             * 获取目录
             */
            register_rest_route($namespace, $path, [
                'methods' => 'GET',
                'permission_callback' => '__RETURN_TRUE',
                'callback' => function ($request) {
                    $user = wp_get_current_user();
                    $post_id = intval($request['postId']);

                    if (!$post_id)
                        return new WP_REST_Response(['title' => __('error-title-no-post-id', 'NovelCabinet'), 'message' => __('error-msg-no-post-id', 'NovelCabinet')], 400);

                    // 获取书
                    $post_id = BookQuery::rootPost($post_id)->ID;

                    if ($user->ID && current_user_can('read_post', $post_id))
                        $contents = new BookContents($post_id, false); // 输出所有文章
                    else
                        $contents = new BookContents($post_id, true); // 输出公开文章


                    return new WP_REST_Response($contents->toJsonArray());
                }
            ]);

            /**
             * 更新目录顺序
             * 只接受层级嵌套的id
             * 传入顺序就是目录顺序
             */
            register_rest_route($namespace, $path, [
                'methods' => 'PATCH',
                'permission_callback' => fn ($request) => current_user_can('edit_post', $request['postId']),
                'callback' => function ($request) {
                    $hierarchy = $request['hierarchy'];
                    try {
                        BookQuery::updateBookHierarchy($hierarchy);
                    } catch (Exception $e) {
                        return static::response(null, $e->getMessage(), 400);
                    }
                    return static::response(null, __('contents-updated-msg', 'NovelCabinet'));
                }
            ]);
        }

        /**
         * abstract REST API representation: 
         * book
         * @return void 
         */
        static function bookRepresentation($namespace, $path = '/posts/(?P<postId>\d+)')
        {
            /**
             * 更新post
             * 如果指定trashed，其它字段将忽略
             * trashed: bool
             * title: string
             */
            register_rest_route($namespace, $path, [
                'methods' => 'PATCH',
                'permission_callback' => fn ($request) => current_user_can('edit_post', $request['postId']),
                'callback' => function ($request) {
                    $postId = intval($request['postId']);

                    /**
                     * 检测文章是否存在
                     */
                    $post = get_post($postId);
                    if (!$post)
                        return static::response(null, __('resource-not-found-msg', 'NovelCabinet'), 404);

                    /**
                     * 指定删除
                     */
                    if (isset($request['trashed'])) {
                        if ($request['trashed'] === true) {
                            wp_trash_post($postId);
                            return static::response(null, __('moved-to-trash-msg', 'NovelCabinet'), extraData: ['status' => __(get_post_status($postId), 'NovelCabinet')]);
                        } else if ($request['trashed'] === false) {
                            wp_untrash_post($postId);
                            return static::response(null, __('restored-from-trash-msg', 'NovelCabinet'), extraData: ['status' => get_post_status($postId), 'NovelCabinet']);
                        } else
                            return static::response(null, __('invalid-trash-field-msg', 'NovelCabinet'), 422);
                    }

                    /**
                     * 更新其它数据
                     */
                    // 过滤有效字段
                    $updated = [];
                    if ($request['title'])
                        $updated['post_title'] = $request['title'];

                    // 没有包含任何需要更新的字段
                    if (count($updated) == 0)
                        return static::response(null, __('no-valid-fields-msg', 'NovelCabinet'), 422);

                    $updated = ['ID' => $postId];
                    $result = wp_update_post($updated);

                    // 检测错误
                    if (is_wp_error($result))
                        return static::response(null, $result->get_error_message(), 422);

                    // 一般化返回
                    return static::response(null, __('post-updated-msg', 'NovelCabinet'));
                }
            ]);

            /**
             * permanently delete a post
             */
            register_rest_route($namespace, $path, [
                'methods' => 'DELETE',
                'permission_callback' => fn ($request) => current_user_can('delete_post', $request['postId']),
                'callback' => function ($request) {
                    if (wp_delete_post(intval($request['postId']), true))
                        return static::response(null, __('successfully-deleted-msg', 'NovelCabinet'));
                    else
                        return static::response(null, __('resource-not-found-msg', 'NovelCabinet'), 404);
                }
            ]);
        }


        static function response(?string $title = null, ?string $message = null, int $status = 200, array $headers = [], array $extraData = [])
        {
            $data = [];
            if ($title || $message) {
                if ($title)
                    $data['title'] = $title;
                if ($message)
                    $data['message'] = $message;
            }
            $data = array_merge($data, $extraData);

            return new WP_REST_Response($data ?: null, $status, $headers);
        }
    }
}
