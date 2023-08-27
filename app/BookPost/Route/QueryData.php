<?php

namespace KarsonJo\BookPost\Route {
    class QueryData
    {
        const KBP_QS_FILTER_GENRE = 'genre';
        const KBP_QS_FILTER_LATEST = 'latest';
        const KBP_QS_FILTER_RATING = 'rating';
        const KBP_QS_FILTER_TIME = 'time';
        const KBP_QS_FILTER_IN_FAVORITE = 'fav';
        const KBP_QS_FILTER_PAGE = 'page';
        const KBP_QS_FILTER_LIMIT = 'limit';

        const KBP_BOOK_STATUS = 'status';
        /**
         * admin query args
         */
        const POST_PARENT = 'post_parent';
        const NEW_CHAPTER_OF = 'chapter_of';
        const NEW_VOLUME_OF = 'volume_of';

        protected static array $args = [];

        /**
         * 注册一个查询字符串
         * add_filter('query_vars')的封装
         * 在此hook之后无效果
         * @param string $key 
         * @return void 
         */
        public static function RegistryQueryArg(string $key)
        {
            $args[] = $key;
        }

        public static function init()
        {
            add_filter('query_vars', function ($vars) {
                $vars[] = static::KBP_QS_FILTER_GENRE;
                $vars[] = static::KBP_QS_FILTER_LATEST;
                $vars[] = static::KBP_QS_FILTER_RATING;
                $vars[] = static::KBP_QS_FILTER_TIME;
                $vars[] = static::KBP_QS_FILTER_IN_FAVORITE;
                $vars[] = static::KBP_QS_FILTER_PAGE;
                $vars[] = static::KBP_QS_FILTER_LIMIT;
                $vars[] = static::KBP_BOOK_STATUS;
                // $a+$b: 如有重复，保持原来
                $vars += static::$args;
                return $vars;
            });
        }

        public static function contains(string $key): bool
        {
            return get_query_var($key, false);
        }

        public static function getAdminQueryArg(string $key, $default = ''): string
        {
            return filter_input(INPUT_GET, $key, FILTER_SANITIZE_ENCODED) ?: $default;
        }


        /**
         * 返回查询字符串请求的所有类别id
         * @return int[] 分割好的类型id
         */
        public static function filterGenre(): array
        {
            $qs = trim(get_query_var(static::KBP_QS_FILTER_GENRE), "-");

            if (!$qs) return [];

            return array_map('intval', explode("-", $qs));
        }

        /**
         * 返回查询字符串请求的时间类别索引
         * @return int 0:无限制 1+:限制的索引
         */
        public static function filterLatest(): int
        {
            // return intval(get_query_var(static::KBP_QS_FILTER_LATEST));
            return static::getPositiveNumber(static::KBP_QS_FILTER_LATEST);
        }

        /**
         * 返回是否指定了评分排序
         * @return int 0:无 1:升序 2:降序
         */
        public static function filterRatingSorting(): int
        {
            return static::isAnySort(static::KBP_QS_FILTER_RATING);
        }

        /**
         * 返回是否指定了时间排序
         * @return int 0:无 1:升序 2:降序
         */
        public static function filterTimeSorting(): int
        {
            return static::isAnySort(static::KBP_QS_FILTER_TIME);
        }

        /**
         * 返回是否指定了收藏夹搜索
         */
        public static function filterInFavorite(): bool
        {
            return get_query_var(static::KBP_QS_FILTER_IN_FAVORITE) === '1';
        }

        public static function filterPage(): int
        {
            // return intval(get_query_var(static::KBP_QS_FILTER_PAGE));
            return static::getPositiveNumber(static::KBP_QS_FILTER_PAGE);
        }

        /**
         * 请求的单页数量
         * @return int 返回0代表无输入
         */
        public static function filterLimit(): int
        {
            // return intval(get_query_var(static::KBP_QS_FILTER_LIMIT));
            return static::getPositiveNumber(static::KBP_QS_FILTER_LIMIT);
        }

        /**
         * 返回是否指定了排序
         * @return int 0:无 1:升序 2:降序
         */
        public static function isAnySort($key)
        {
            $var = strtolower(get_query_var($key));
            if ($var == 'asc')
                return 1;
            if ($var == 'desc')
                return 2;
            return 0;
        }

        /**
         * 是否设置了chapter_of查询字符串
         * 如果没有，返回0
         */
        public static function chapterOf(): int
        {
            return static::getPositiveNumber(static::NEW_CHAPTER_OF);
        }

        /**
         * 提取一个正整数查询字符串
         * @param mixed $key 
         * @return int 
         */
        protected static function getPositiveNumber($key): int
        {
            $num = static::getAdminQueryArg($key);
            if (empty($num) || !is_numeric($num))
                return 0;
            return intval($num);
        }
    }
}
