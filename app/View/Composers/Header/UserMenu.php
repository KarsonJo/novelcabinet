<?php

namespace App\View\Composers\Header;

use Roots\Acorn\View\Composer;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

use function KarsonJo\BookRequest\get_user_login_url;
use function NovelCabinet\Utility\home_url_trailingslashit;

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
        // $path = get_bloginfo('url');
        if ($login) {
            $avatar = get_avatar_url($user->ID);
            $profile_url = site_url('/wp-admin/profile.php');
            //admin
            if (current_user_can('level_10')) {
                $items[] = [site_url('/wp-admin/'), __('dashboard', 'NovelCabinet'), true]; //dashboard
                $items[] = [site_url('/wp-admin/post-new.php'), __('new-post', 'NovelCabinet'), true]; //new post
            }
            //user
            $items[] = [$profile_url, __('profile', 'NovelCabinet'), true]; //profile
            $items[] = [wp_logout_url(home_url_trailingslashit()), __('sign-out', 'NovelCabinet'), false]; //sign out

        } else {
            //visitor
            $avatar = get_avatar_url(0);

            $profile_url = get_user_login_url();
            // $profile_url = wp_login_url();
            $items[] = [$profile_url, __('sign-in', 'NovelCabinet'), false];
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
                'blank' => $item[2]
            ];
        }, $menu);
    }
}
