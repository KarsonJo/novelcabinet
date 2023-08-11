<?php

namespace KarsonJo\BookRequest;

/**
 * 每次载入前调用
 * 提供页面相关的支持
 */

/**
 * 形如：my.site/user/(xxxx)/
 * 键随意，值才是slug
 */
enum UserEndpoints: string
{
    case Settings = 'settings';
    case Main = 'main';
    case Writing = 'writing';
    case Books = 'books';

    public static function sigments(): array
    {
        return array_column(UserEndpoints::cases(), 'value');
    }
}

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
        '^user/(?P<endpoint>' . implode('|', UserEndpoints::sigments()) . ')?/?$'
    ],
    locate_template(app('sage.finder')->locate('user')),
    [
        fn () => !is_user_logged_in() && wp_redirect(get_user_login_url(), 301),
        fn () => Router::atPath('^user/?$') && wp_redirect(get_user_home_url(UserEndpoints::Settings), 301),
    ]
);
// https://my.site/login/
Router::registerRoute(
    '^login/?$',
    locate_template(app('sage.finder')->locate('login')),
    fn () => is_user_logged_in() && wp_redirect(get_user_home_url(), 301)
);
// https://my.site/external-redirect
Router::registerRoute(
    '^external-redirect/?$',
    locate_template(app('sage.finder')->locate('external-redirect')),
    // fn () => is_user_logged_in() && wp_redirect(get_user_home_url()) and exit
);
// https://my.site/date/1970/01/01
Router::registerRoute(
    '^date/(?P<year>\d{4})/(?P<month>\d{2})/(?P<day>\d{2})/?$',
    locate_template(app('sage.finder')->locate('login')),
    // fn () => is_user_logged_in() && wp_redirect(get_user_home_url()) and exit
);
Router::init();


echo '<a href="//google.com">123</a>';




// 验证修改邮箱，然后重定向
// add_action('profile_update', fn () => is_admin() && !empty($_GET['dismiss']) && wp_redirect(get_user_home_url()) and exit);
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
            return get_user_home_url(); //重定向到home
    }

    return $location;
});

/**
 * 返回用户的home链接，根据Permalink规则决定是否补斜杠
 * @param string|UserEndpoints $subpath 紧随其后的路径
 */
function get_user_home_url(string|UserEndpoints $subpath = UserEndpoints::Settings)
{
    if ($subpath instanceof UserEndpoints)
        $subpath = "/$subpath->value";
    return user_trailingslashit(home_url("/user$subpath"));
}

/**
 * 返回主题的登录链接，根据Permalink规则决定是否补斜杠
 */
function get_user_login_url()
{
    return user_trailingslashit(home_url('/login'));
}


/**
 * 页面路由
 */
class Router
{
    /**
     * 当前活跃的路由路径
     */
    protected static ?string $activePath = null;

    /**
     * 活跃路由的参数
     */
    public static ?array $data = null;

    /**
     * 注册一个路由路径
     * @param string|string[] $routePaths 一个或多个路径，正则表达式，站点名后的路径部分
     * @param string $template 加载的blade or php文件
     * @param ?callable|callable[] $redirects 可选的重定向逻辑，在该路由生效时在template_redirect触发，传入的重定向函数只需返回布尔值，不必退出
     */
    public static function registerRoute(string|iterable $routePaths, string $template, null|callable|iterable $redirects = null)
    {
        if (!$routePaths)
            return;

        if (is_string($routePaths))
            $routePaths = [$routePaths];
        /**
         * 记录到重写规则
         */
        foreach ($routePaths as $routePath)
            add_action('init', fn () => add_rewrite_rule($routePath, 'index.php', 'top'));
        /**
         * 匹配url并设置标记变量，使用最早能够获得url的钩子：
         * https://wordpress.stackexchange.com/questions/317760/how-do-i-know-if-a-rewritten-rule-was-applied
         */
        add_action('parse_request', function ($wp) use ($routePaths, $template, $redirects) {
            foreach ($routePaths as $routePath)
                if (preg_match("<$routePath>", $wp->request, $matches)) {
                    // Router::setActivePath($routePath);
                    Router::setActiveRoute($routePath, $template, $redirects);
                    Router::$data = Router::filterMatches($matches);
                    break;
                }
        });
    }

    public static function init()
    {
        // 如果是初次：刷新
        add_action('after_switch_theme', 'flush_rewrite_rules');
    }

    protected static function setActiveRoute(string $routePath, string $template, null|callable|iterable $redirects = null)
    {
        Router::$activePath = $routePath;

        /**
         * 更改页面模板
         */
        add_filter('template_include', fn () =>  $template);

        /**
         * 加入重定向逻辑
         * 优先级设为9抢先在redirect_canonical之前执行，否则可能触发无意义的canonical redirect
         */
        if (is_string($redirects))
            $redirects = [$redirects];

        if ($redirects)
            foreach ($redirects as $redirect)
                add_action('template_redirect', fn () => $redirect() and exit(), 9, 0);

        /**
         * 抑制主查询
         */
        add_filter('posts_request', fn ($request, $query) => $query->is_main_query() ? false : $request, 10, 2);

        /**
         * 两件事：
         * 1. 将$wp_query->is_home设置为false，以免redirect_canonical中被标志为home页面强行加末尾斜杠
         * 2. 抑制由于“抑制主查询+is_home=false”产生的404
         */
        add_filter('pre_handle_404', fn ($_, $wp_query) => $wp_query->init_query_flags() || true, 10, 2);
    }

    protected static function filterMatches($matches)
    {
        return array_filter($matches, fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);
    }

    // 下面的函数没用到，但你可能需要这些函数做一些控制
    public static function atPath($path): bool
    {
        return !is_404() && Router::$activePath === $path;
    }

    public static function atAnyPath(): bool
    {
        return Router::$activePath !== null;
    }

    public static function activePath(): ?string
    {
        return Router::$activePath;
    }
}

// add_filter('rewrite_rules_array', function ($rules) {
//     // echo "fuck";
//     // foreach($rules as $rule) {
//     //     print_r($rule);
//     //     echo "<br/>";
//     // }
//     var_dump($rules);
//     return $rules;
// });

// add_filter('redirect_canonical', function ($redirect, $request) {
//     print_r("redirect $redirect");
//     echo "<br/>";
//     print_r("request $request");
//     return $request;
// }, 10, 2);


// add_filter('posts_request', 'KarsonJo\BookRequest\supress_main_query', 10, 2);
// function supress_main_query($request, $query)
// {
//     if ($query->is_main_query() && !$query->is_admin)
//         return false;
//     else
//         return $request;
// }

// ========== 自定义页面路由 ==========
// ========== eg. https://my.site/bookfinder ==========

/**
 * 做法1，使用重写规则+查询字符串标记（未使用）
 * https://stackoverflow.com/questions/25310665/wordpress-how-to-create-a-rewrite-rule-for-a-file-in-a-custom-plugin
 * 
 * 缺点是会污染查询字符串
 */



/**
 * 做法2，使用重写规则+正则匹配+变量标记
 * 
 * 
 */
// function bookfinder_rewrite()
// {
//     //原地跳转，只为记录路径
//     add_rewrite_rule('^' . 'bookfinder' . '$', 'index.php', 'top');
//     Router::setBookFinderRoute("bfd");
// }
// add_action('init', 'KarsonJo\\BookRequest\\bookfinder_rewrite');

// function modify_query_vars($wp)
// {
//     // print_r($wp->request);
//     if (preg_match('#^bookfinder/?.*?#', $wp->request))
//         // Router::$flags[BOOKFINDER_FLAG_NAME] = true;
//         Router::setBookFinder();
// }
// add_action('parse_request', 'KarsonJo\\BookRequest\\modify_query_vars'); // $wp->request设置后尽快调用


/**
 * 检测标记并选择性进行页面加载
 */
// function bookfinder_template($template)
// {
//     if (Router::isBookFinder()) {
//         // return get_template_directory() . '/resources/views/book-finder.blade.php';
//         // https://discourse.roots.io/t/load-a-specific-blade-template-for-custom-url-structure-with-wp-rewrite/22951
//         return locate_template(app('sage.finder')->locate('book-finder'));
//         // return locate_template('/resources/views/book-finder.blade.php');
//     }
//     return $template;
// }
// add_filter('template_include', 'KarsonJo\\BookRequest\\bookfinder_template');

/**
 * 下面俩函数 处理前缀斜杠重定向，与固定链接的格式一致
 * 通常是redirect_canonical()直接处理，但似乎对这个链接总是重定向至/结尾版本
 * 因此自己处理，算是workaround吧
 */
// add_filter('redirect_canonical', function ($redirect) {
//     return Router::isBookFinder() ? false : $redirect;
// }, 10, 2);
// add_action('template_redirect', function () {
//     if (Router::isBookFinder()) {
//         $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
//         $uri_parts[0] = user_trailingslashit($uri_parts[0]);
//         $uri = implode('?', $uri_parts);

//         if ($_SERVER['REQUEST_URI'] != $uri) {
//             wp_redirect($uri, 301);
//             exit;
//         }
//     }
// });




// // 初次：刷新
// add_action('after_switch_theme', 'flush_rewrite_rules');
