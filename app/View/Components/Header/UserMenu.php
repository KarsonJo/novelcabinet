<?php

namespace App\View\Components\Header;

use Illuminate\View\Component;
use NovelCabinet\Helpers\WebHelpers;

use function NovelCabinet\Utility\home_url_trailingslashit;

class UserMenu extends Component
{
    public bool $logged;
    public string $avatar;
    public string $profileUrl;
    public string $name;
    public array $menu;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->logged = is_user_logged_in();
        
        $user = wp_get_current_user();
        $items = [];
        // $path = get_bloginfo('url');
        if ($this->logged) {
            $this->avatar = get_avatar_url($user->ID);
            // $this->profileUrl = site_url('/wp-admin/profile.php');
            $this->profileUrl = WebHelpers::getUserHomeUrl();

            //admin
            if (current_user_can('level_10')) {
                $items[] = [site_url('/wp-admin/'), __('dashboard', 'NovelCabinet'), true]; //dashboard
                $items[] = [site_url('/wp-admin/post-new.php'), __('new-post', 'NovelCabinet'), true]; //new post
            }
            //user
            $items[] = [$this->profileUrl, __('profile', 'NovelCabinet'), true]; //profile
            $items[] = [wp_logout_url(home_url_trailingslashit()), __('sign-out', 'NovelCabinet'), false]; //sign out

        } else {
            //visitor
            $this->avatar = get_avatar_url(0);

            $this->profileUrl = WebHelpers::getUserLoginUrl();
            // $profileUrl = wp_login_url();
            $items[] = [$this->profileUrl, __('sign-in', 'NovelCabinet'), false]; //sign in
        }

        $this->name = $user->display_name;
        $this->menu = $this->map_user_menu($items);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.header.user-menu');
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
