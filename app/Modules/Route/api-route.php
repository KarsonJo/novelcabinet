<?php

namespace KarsonJo\BookPost\RESTAPI {

    use Exception;
    use WP_REST_Response;

    use KarsonJo\BookPost\SqlQuery as Query;
    use Symfony\Component\Mime\Message;

    use function KarsonJo\BookPost\SqlQuery\create_user_favorite_list;
    use function KarsonJo\BookPost\SqlQuery\get_book_rating;
    use function KarsonJo\BookPost\SqlQuery\set_book_rating;

    const API_DOMAIN = 'kbp';
    const API_VERSION = 'v1';
    const API_NAMESPACE = API_DOMAIN . '/' . API_VERSION;

    function register_rest_routes()
    {
        // 书本评分
        register_rest_route(API_NAMESPACE, '/rate/(?P<postId>\d+)', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $post_id = $request['postId'];
                $rating = $request['rating'];
                $user = wp_get_current_user();

                if (!$user->ID)
                    return new WP_REST_Response(['message' => '需要登录才能评分喔'], 401);
                // return new \WP_Error('invalid_user', '需要登录才能评分喔', array('status' => 401));

                try {
                    Query\set_book_rating($post_id, $user, $rating);
                } catch (Exception) {
                    return new WP_REST_Response(['message' => '已经评分过了'], 400);
                    // return new \WP_Error('rating_failed', '已经评分过了', array('status' => 400));
                }

                return [
                    'message' => '评分成功',
                    'id' => $post_id,
                    'userRating' => $rating,
                    'avgRating' => Query\get_book_rating($post_id),
                ];
            }
        ));

        // 创建收藏夹
        register_rest_route(API_NAMESPACE, '/fav/create', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $user = wp_get_current_user();
                $title = $request['title'];
                $visibility = $request['visibility'];

                if (!$user->ID)
                    return new WP_REST_Response(['message' => '需要登录才能收藏喔'], 401);

                try {
                    $id = Query\create_user_favorite_list($user, $title, $visibility);
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

        // 更新用户对某篇文章收藏情况
        register_rest_route(API_NAMESPACE, '/post-fav/update/(?P<postId>\d+)', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $user = wp_get_current_user();
                $post_id = $request['postId'];
                $fav_lists = $request['favLists'];

                if (!$user->ID)
                    return new WP_REST_Response(['message' => '需要登录才能收藏喔'], 401);

                try {
                    Query\update_user_post_favorite($post_id, $user, $fav_lists);
                } catch (Exception $e) {
                    return new WP_REST_Response(['title' => '收藏失败', 'message' => $e->getMessage()], 400);
                }
                return new WP_REST_Response(['title' => '收藏成功', 'message' => "文章收藏状态已更新"]);
            }
        ));
    }
    add_action('rest_api_init', 'KarsonJo\BookPost\RESTAPI\register_rest_routes');



    function set_api_javascript_data()
    {
        wp_enqueue_script('wp-api');
        wp_localize_script('wp-api', 'wpApiSettings', array(
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }
    add_action('wp_enqueue_scripts', 'KarsonJo\BookPost\RESTAPI\set_api_javascript_data');
}
