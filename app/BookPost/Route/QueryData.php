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

                return $vars;
            });
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
            return intval(get_query_var(static::KBP_QS_FILTER_LATEST));
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
            return intval(get_query_var(static::KBP_QS_FILTER_PAGE));
        }

        /**
         * 请求的单页数量
         * @return int 返回0代表无输入
         */
        public static function filterLimit(): int
        {
            return intval(get_query_var(static::KBP_QS_FILTER_LIMIT));
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
    }
}
