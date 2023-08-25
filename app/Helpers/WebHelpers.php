<?php

namespace NovelCabinet\Helpers {

    use NovelCabinet\Services\Route\Enums\UserBookEndpoints;
    use NovelCabinet\Services\Route\Enums\UserEndpoints;

    class WebHelpers
    {
        /**
         * 返回用户的home链接，根据Permalink规则决定是否补斜杠
         * @param string|UserEndpoints $subpath 紧随其后的路径
         */
        public static function getUserHomeUrl(string|UserEndpoints $subpath = UserEndpoints::Settings)
        {
            if ($subpath instanceof UserEndpoints)
                $subpath = "/$subpath->value";
            return user_trailingslashit(home_url("/user$subpath"));
        }

        // public static function getUserBookUrl(string|UserBookEndpoints $subpath = UserBookEndpoints::Publish)
        // {
        //     if ($subpath instanceof UserBookEndpoints)
        //         $subpath = "/$subpath->value";
        //     if (trailingslashit($subpath) === '/' . UserBookEndpoints::Publish->value)
        //         $subpath = "/";
        //     return static::getUserHomeUrl('/' . UserEndpoints::Books->value . $subpath);
        // }

        /**
         * 返回当前url， 按设定补斜杠
         * @return string 
         */
        public static function currentUrl(): string
        {
            global $wp;
            return user_trailingslashit(home_url($wp->request));
        }

        /**
         * 返回主题的登录链接，根据Permalink规则决定是否补斜杠
         */
        public static function getUserLoginUrl()
        {
            return user_trailingslashit(home_url('/login'));
        }
    }
}
