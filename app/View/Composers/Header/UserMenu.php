<?php

namespace App\View\Composers\Header;

use Roots\Acorn\View\Composer;

class UserMenu extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'partials.header.user-menu',
    ];

    /**
     * Data to be passed to view before rendering, but after merging.
     *
     * @return array
     */
    public function override()
    {
        $user = wp_get_current_user();
        $login = is_user_logged_in();
        $items = [];
        $path = get_bloginfo('url');
        if ($login) {
            $avatar = get_avatar_url($user->ID);
            $profile_url = $path . '/wp-admin/profile.php';
            //admin
            if (current_user_can('level_10')) {
                $items[] = [$path . '/wp-admin/', __('Dashboard', 'novelcabi'), false]; //dashboard
                $items[] = [$path . '/wp-admin/post-new.php', __('New Post', 'novelcabi'), false]; //new post
            }
            //user
            $items[] = [$profile_url, __('Profile', 'sakura'), false]; //profile
            $items[] = [wp_logout_url($path), __('Sign out', 'novelcabi'), true]; //sign out

        } else {
            //visitor
            $avatar = 'https://cdn.jsdelivr.net/gh/moezx/cdn@3.1.9/img/Sakura/images/none.png';
            
            $profile_url = $path . '/wp-login.php';
            $items[] = [$profile_url, __('Login', 'novelcabi'), false]; //Login
        }
        
        return [
            'logged' => $login,
            'avatar' => $avatar,
            'profile_url' => $profile_url,
            'name' => $user->display_name,
            'menu' => $this->map_user_menu($items),
        ];
    }

    private function map_user_menu($menu)
    {
        return array_map(function ($item) {
            return [
                'url' => $item[0],
                'title' => $item[1],
                'top' => $item[2]
            ];
        }, $menu);
    }
}
