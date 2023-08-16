<?php

namespace KarsonJo\BookRequest\QueryData;

use function KarsonJo\BookRequest\is_book_finder;

/**
 * 每次载入前调用
 * 提供查询字符串相关的支持
 */

// ========== book finder ==========

// const KBP_QS_FILTER_GENRE = 'genre';
// const KBP_QS_FILTER_LATEST = 'latest';
// const KBP_QS_FILTER_RATING = 'rating';
// const KBP_QS_FILTER_TIME = 'time';
// const KBP_QS_FILTER_IN_FAVORITE = 'fav';
// const KBP_QS_FILTER_PAGE = 'page';

define('KBP_QS_FILTER_GENRE', 'genre');
define('KBP_QS_FILTER_LATEST', 'latest');
define('KBP_QS_FILTER_RATING', 'rating');
define('KBP_QS_FILTER_TIME', 'time');
define('KBP_QS_FILTER_IN_FAVORITE', 'fav');
define('KBP_QS_FILTER_PAGE', 'page');
define('KBP_QS_FILTER_LIMIT', 'limit');

function add_query_vars_filter($vars)
{
    $vars[] = KBP_QS_FILTER_GENRE;
    $vars[] = KBP_QS_FILTER_LATEST;
    $vars[] = KBP_QS_FILTER_RATING;
    $vars[] = KBP_QS_FILTER_TIME;
    $vars[] = KBP_QS_FILTER_IN_FAVORITE;
    $vars[] = KBP_QS_FILTER_PAGE;
    $vars[] = KBP_QS_FILTER_LIMIT;

    return $vars;
}


add_filter('query_vars', 'KarsonJo\\BookRequest\\QueryData\\add_query_vars_filter');

/**
 * 返回查询字符串请求的所有类别id
 * @return int[] 分割好的类型id
 */
function get_filter_genre(): array
{
    $qs = trim(get_query_var(KBP_QS_FILTER_GENRE), "-");

    if (!$qs) return [];

    return array_map('intval', explode("-", $qs));
}

/**
 * 返回查询字符串请求的时间类别索引
 * @return int 0:无限制 1+:限制的索引
 */
function get_filter_latest(): int
{
    return intval(get_query_var(KBP_QS_FILTER_LATEST));
}

/**
 * 返回是否指定了评分排序
 * @return int 0:无 1:升序 2:降序
 */
function get_filter_rating_sorting(): int
{
    return _is_any_sort(KBP_QS_FILTER_RATING);
}

/**
 * 返回是否指定了时间排序
 * @return int 0:无 1:升序 2:降序
 */
function get_filter_time_sorting(): int
{
    return _is_any_sort(KBP_QS_FILTER_TIME);
}

/**
 * 返回是否指定了收藏夹搜索
 */
function get_filter_in_favorite(): bool
{
    return get_query_var(KBP_QS_FILTER_IN_FAVORITE) === '1';
}

function get_filter_page(): int
{
    return intval(get_query_var(KBP_QS_FILTER_PAGE));
}

/**
 * 请求的单页数量
 * @return int 返回0代表无输入
 */
function get_filter_limit(): int
{
    return intval(get_query_var(KBP_QS_FILTER_LIMIT));
}

/**
 * 返回是否指定了排序
 * @return int 0:无 1:升序 2:降序
 */
function _is_any_sort($key)
{
    $var = strtolower(get_query_var($key));
    if ($var == 'asc')
        return 1;
    if ($var == 'desc')
        return 2;
    return 0;
}
