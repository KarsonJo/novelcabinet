<?php

namespace App\View\Composers\Header;

use NovelCabinet\Helpers\WebHelpers;
use Roots\Acorn\View\Composer;

use function NovelCabinet\Utility\home_url_trailingslashit;

class Header extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'sections.header',
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    public function with()
    {
        $user = wp_get_current_user();
        if ($user->ID) {
            $signUrl = wp_logout_url(home_url_trailingslashit());
            $sign = __('sign-out', 'NovelCabinet');
        }
        else {
            $signUrl = WebHelpers::getUserLoginUrl();
            $sign = __('sign-in', 'NovelCabinet');
        }
        return [
            'navBarItems' => $this->getNavigationItem('primary_navigation'),
            'user' => wp_get_current_user(),
            'signUrl' => $signUrl,
            'sign' => $sign,
        ];
    }

    public function getNavigationItem($theme_location)
    {
        if ( ($theme_location) && ($locations = get_nav_menu_locations()) && isset($locations[$theme_location]) ) {
            $menu = get_term( $locations[$theme_location], 'nav_menu' );
            $menu_items = wp_get_nav_menu_items($menu->term_id);
            // write_log($menu_items);
            return $menu_items;
        }
        else {
            return false;
        }
        
    }
}
