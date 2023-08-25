<?php

namespace NovelCabinet\Services\Route\Enums {
    /**
     * 形如：my.site/user/books/(xxxx)/
     * 键随意，值才是slug
     */
    enum UserBookEndpoints: string
    {
        case Publish = 'publish';
        case Future = 'future';
        case Trash = 'trash';

        public static function sigments(): array
        {
            return array_column(static::cases(), 'value');
        }
    }
}
