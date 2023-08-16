<?php

namespace NovelCabinet\Services\Route\Enums {
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
}
