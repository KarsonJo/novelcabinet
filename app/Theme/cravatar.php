<?php

namespace NovelCabinet\Cravatar {

    // https://cravatar.cn/developer/for-wordpress

    /**
     * 替换 Gravatar 头像为 Cravatar 头像
     *
     * Cravatar 是 Gravatar 在中国的完美替代方案，你可以在 https://cravatar.cn 更新你的头像
     */
    $function =  function ($url) {
        $sources = array(
            'www.gravatar.com',
            '0.gravatar.com',
            '1.gravatar.com',
            '2.gravatar.com',
            'secure.gravatar.com',
            'cn.gravatar.com',
            'gravatar.com',
        );
        return str_replace($sources, 'cravatar.cn', $url);
    };
    add_filter('um_user_avatar_url_filter', $function, 1);
    add_filter('bp_gravatar_url', $function, 1);
    add_filter('get_avatar_url', $function, 1);

    /**
     * 替换 WordPress 讨论设置中的默认头像
     */
    $function = function ($avatar_defaults) {
        $avatar_defaults['gravatar_default'] = 'Cravatar 标志';
        return $avatar_defaults;
    };
    add_filter('avatar_defaults', $function, 1);

    /**
     * 替换个人资料卡中的头像上传地址
     */
    $function = fn () => '<a href="https://cravatar.cn" target="_blank">您可以在 Cravatar 修改您的资料图片</a>';
    add_filter('user_profile_picture_description', $function, 1);
}
