<?php

namespace NovelCabinet\Helpers {

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

        /**
         * 返回主题的登录链接，根据Permalink规则决定是否补斜杠
         */
        public static function getUserLoginUrl()
        {
            return user_trailingslashit(home_url('/login'));
        }
    }
}
