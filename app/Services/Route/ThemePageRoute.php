<?php

namespace NovelCabinet\Services\Route {

    use KarsonJo\Utilities\Route\Router;
    use NovelCabinet\Helpers\WebHelpers;
    use NovelCabinet\Services\Route\Enums\UserBookEndpoints;
    use NovelCabinet\Services\Route\Enums\UserEndpoints;

    /**
     * 每次载入前调用
     * 提供页面相关的支持
     */

    class ThemePageRoute
    {
        public static function init()
        {
            static::initRoutes();
            static::initRedirects();
        }

        private static function initRoutes()
        {
            // $template = get_template_directory() . '/resources/views/book-finder.blade.php';
            // more robust: https://discourse.roots.io/t/load-a-specific-blade-template-for-custom-url-structure-with-wp-rewrite/22951
            // https://my.site/bookfinder/
            Router::registerRoute(
                '^bookfinder/?$',
                locate_template(app('sage.finder')->locate('book-finder'))
            );

            // https://my.site/user/(xxxx)/
            Router::registerRoute(
                [
                    '^user/?$',
                    '^user/(?P<userEndpoint>' . implode('|', UserEndpoints::sigments()) . ')/?$',
                    // '^user/(?P<userEndpoint>'. UserEndpoints::Books->value .')/(?P<bookEndpoint>'. implode('|', UserBookEndpoints::sigments()) .')/?$',
                ],
                locate_template(app('sage.finder')->locate('user')),
                [
                    fn () => !is_user_logged_in() && wp_redirect(WebHelpers::getUserLoginUrl(), 302),
                    fn () => Router::atPath('^user/?$') && wp_redirect(WebHelpers::getUserHomeUrl(UserEndpoints::Settings), 301),
                ]
            );
            // https://my.site/login/
            Router::registerRoute(
                '^login/?$',
                locate_template(app('sage.finder')->locate('login')),
                fn () => is_user_logged_in() && wp_redirect(WebHelpers::getUserHomeUrl(), 302)
            );
            // https://my.site/external-redirect
            Router::registerRoute(
                '^external-redirect/?$',
                locate_template(app('sage.finder')->locate('external-redirect')),
                // fn () => is_user_logged_in() && wp_redirect(WebHelpers::getUserHomeUrl()) and exit
            );
            // https://my.site/date/1970/01/01
            Router::registerRoute(
                '^date/(?P<year>\d{4})/(?P<month>\d{2})/(?P<day>\d{2})/?$',
                locate_template(app('sage.finder')->locate('login')),
                // fn () => is_user_logged_in() && wp_redirect(WebHelpers::getUserHomeUrl()) and exit
            );
            Router::init();
        }

        private static function initRedirects()
        {
            // 验证修改邮箱，然后重定向
            add_filter('wp_redirect', function ($location) {
                //没有相关数据，绝对不是修改邮箱链接，直接返回
                if (!(isset($_GET['newuseremail']) || isset($_GET['dismiss'])) || !get_current_user_id())
                    return $location;

                // 解析URL
                $url_parts = parse_url($location);
                $profile_parts = parse_url(self_admin_url('profile.php'));

                //详细检测是否是邮箱修改链接触发的重定向
                if (untrailingslashit($url_parts['path']) === untrailingslashit($profile_parts['path'])) {
                    $query = $url_parts['query'] ?? '';
                    parse_str($query, $params);

                    if (
                        isset($params['updated']) && $params['updated'] == true
                        || isset($params['error']) && $params['error'] === 'new-email'
                    )
                        return WebHelpers::getUserHomeUrl(); //重定向到home
                }

                return $location;
            });

            // 子书籍返回404如果父书籍被设置为私有或草稿
            add_filter('pre_handle_404', function ($_, $wp_query) {
                if (empty($wp_query->post))
                    return false;
                $ancestor_id = last(get_post_ancestors($wp_query->post->ID));
                // 是子文章，且无权访问爷/爹
                if ($ancestor_id && !current_user_can('read_post', $ancestor_id)) {
                    //清空文章
                    $wp_query->posts = [];
                    unset($wp_query->post);
                    $wp_query->post_count = 0;

                    // print_r($wp_the_query->post);
                    // wp_reset_query();
                    // print_r($wp_the_query->post);
                    // print_r("user cant read this");
                    //设置404
                    $wp_query->set_404();
                    status_header(404);
                    nocache_headers();
                }
                return false;
            }, 10, 2);
        }
    }
}
