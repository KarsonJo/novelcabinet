<?php

namespace KarsonJo\Utilities\Route {
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
}
