<?php

namespace NovelCabinet\Services\Route {

    use NovelCabinet\Helpers\WebHelpers;
    use WP_REST_Response;
    use function NovelCabinet\User\validate_gender;
    use function NovelCabinet\Utility\validate_date;

    abstract class APIRoute
    {
        public static function init($apiDomain = 'knc', $apiVersion = 'v1')
        {
            $namespace = $apiDomain . '/' . $apiVersion;

            add_action('rest_api_init', function () use ($namespace) {
                static::loginAPI($namespace);
                static::updateUserDataAPI($namespace);
            });
        }

        static function loginAPI($namespace, $path = '/login')
        {
            register_rest_route($namespace, $path, array(
                'methods' => 'POST',
                'permission_callback' => '__RETURN_TRUE',
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
                            'location' => $redirect_to ?: WebHelpers::getUserHomeUrl(),
                        ], 302);
                }
            ));
        }

        /**
         * fields: 
         * displayName, lastName, firstName, userDescription, birthDate, gender,
         * currPassword, newPassword, email
         */
        static function updateUserDataAPI($namespace, $path = '/userdata/update')
        {
            register_rest_route($namespace, $path, [
                'methods' => 'POST',
                'permission_callback' => '__RETURN_TRUE',
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
            ]);
        }
    }
}

namespace NovelCabinet\RESTAPI {
}
