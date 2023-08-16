<?php

namespace KarsonJo\BookPost\Route {

    use Exception;
    use WP_REST_Response;

    use KarsonJo\BookPost\SqlQuery as Query;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use Symfony\Component\Mime\Message;

    class APIRoute
    {
        public static function init($apiDomain = 'kbp', $apiVersion = 'v1')
        {
            $namespace = $apiDomain . '/' . $apiVersion;

            add_action('rest_api_init', function () use ($namespace) {
                static::bookRating($namespace);
                static::createFavoriteList($namespace);
                static::updatePostFavorite($namespace);
            });
        }

        static function bookRating($namespace, $path = '/rate/(?P<postId>\d+)')
        {
            register_rest_route($namespace, $path, array(
                'methods' => 'POST',
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
    }
}
