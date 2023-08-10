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

                return new WP_REST_Response([
                    'message' => '评分成功',
                    'id' => $post_id,
                    'userRating' => $rating,
                    'avgRating' => Query\get_book_rating($post_id),
                ]);
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

namespace NovelCabinet\RESTAPI {
    const API_DOMAIN = 'knc';
    const API_VERSION = 'v1';
    const API_NAMESPACE = API_DOMAIN . '/' . API_VERSION;

    use WP_REST_Request;
    use WP_REST_Response;

    use function KarsonJo\BookRequest\get_user_home_url;
    use function NovelCabinet\User\get_gender;
    use function NovelCabinet\User\validate_gender;
    use function NovelCabinet\Utility\validate_date;

    add_action('rest_api_init', function () {
        // 登录
        register_rest_route(API_NAMESPACE, '/login', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $username = $request['username'];
                $password = $request['password'];
                $remember = $request['remember'];
                $redirect_to = $request['redirectTo'];

                $user = wp_signon(array(
                    'user_login' => $username,
                    'user_password' => $password,
                    'remember' => $remember // 是否记住登录状态
                ));

                if (is_wp_error($user))
                    return new WP_REST_Response(['message' => $user->get_error_message()], 401);
                else
                    return new WP_REST_Response([
                        'message' => '登录成功',
                        'location' => $redirect_to ?: get_user_home_url(),
                    ], 302);
            }
        ));

        /**
         * fields: 
         * displayName, lastName, firstName, userDescription, birthDate, gender,
         * currPassword, newPassword, email
         */
        register_rest_route(API_NAMESPACE, '/userdata/update', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                global $errors;
                $user = wp_get_current_user();

                if (!$user->ID)
                    return new WP_REST_Response(['message' => '请先登录', 'type' => 'error'], 401);

                /**
                 * 'fieldName' => 
                 * [
                 *      'disabled' => true/false,
                 *      'messages' => string[]
                 * ]
                 */
                $response_fields = [];
                $userdata = [];
                $userdata['ID'] = $user->ID;


                $new_email = trim($request['email']); //字符串处理
                $pending_email = get_user_meta($user->ID, '_new_email', true)['newemail'] ?? false; //用户当前等待认证的email，存在时忽略本次邮箱修改
                $email_changed = !empty($new_email) && $user->user_email !== $new_email; //输入的email

                // 如修改了敏感信息，检测用户当前的密码
                if (($email_changed && !$pending_email) || !empty($request['newPassword']))
                    if (!wp_check_password($request['currPassword'], $user->data->user_pass, $user->ID))
                        return new WP_REST_Response(['message' => '原始密码错误', 'type' => 'error'], 400); // 错误时驳回


                // 收集密码
                if (!empty($request['newPassword']))
                    $userdata['user_pass'] = $request['newPassword'];

                // 修改邮箱
                if ($email_changed) {
                    if ($pending_email)
                        $response_fields['email'] = ['locked' => true, 'messages' => [sprintf(__('user-msg-email-pending', 'NovelCabinet'), " <span>$pending_email</span> ")]];
                    else {

                        $_POST['user_id'] = $user->ID;
                        $_POST['email'] = $new_email;

                        // add_filter('wp_mail', fn ($args) => print_r($args['message']));
                        $success = send_confirmation_on_profile_email();
                        // print_r($success ? "true" : "false");
                        if ($errors->get_error_code())
                            return new WP_REST_Response(['message' => $errors->get_error_message(), 'type' => 'error']);
                        else if ($success !== false)
                            $response_fields['email'] = ['locked' => true, 'messages' => ['已发送确认邮件至您的邮箱']];
                    }
                }
                // $response_fields['email'] = ['disabled' => true, 'messages' => ['已发送确认邮件至您的邮箱']];

                // 收集基本信息
                if (!empty($request['displayName']))
                    $userdata['display_name'] = $request['displayName'];
                if (!empty($request['lastName']))
                    $userdata['last_name'] = $request['lastName'];
                if (!empty($request['firstName']))
                    $userdata['first_name'] = $request['firstName'];
                if (!empty($request['userDescription']))
                    $userdata['description'] = $request['userDescription'];

                if (!empty($request['birthdate']) && validate_date($request['birthdate']))
                    $userdata['meta_input']['birthdate'] = $request['birthdate'];
                if (!empty($request['gender']) && validate_gender($request['gender']))
                    $userdata['meta_input']['gender'] = $request['gender'];

                if ($userdata)
                    $result = wp_update_user($userdata);
                // print_r($userdata);
                // print_r($request);
                // return [];

                if (is_wp_error($result))
                    return new WP_REST_Response(['message' => $result->get_error_message(), 'type' => 'error'], 400);
                else
                    return new WP_REST_Response(['message' => '账号信息已更新', 'type' => 'success', 'fields' => $response_fields], 200);
            }
        ));
    });
}
