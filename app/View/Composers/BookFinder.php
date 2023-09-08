<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

use KarsonJo\BookPost\Route\QueryData;
use KarsonJo\BookPost\SqlQuery\BookFilterBuilder;
use KarsonJo\BookPost\SqlQuery\BookQuery;

class BookFinder extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var string[]
     */
    protected static $views = [
        'sections.book-finder'
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    public function override()
    {

        $genre = QueryData::filterGenre();
        $latest = QueryData::filterLatest();
        $rating = QueryData::filterRatingSorting();
        $time = QueryData::filterTimeSorting();
        $fav = QueryData::filterInFavorite();
        $page = max(QueryData::filterPage(), 1);
        $limit = QueryData::filterLimit();
        $limit = max(1, min(100, $limit ? $limit : 20));

        $builder = BookFilterBuilder::create();
        if ($genre)
            $builder->of_all_genres($genre);
        if ($latest)
            $builder->of_latest($latest);
        if ($rating)
            $builder->order_by_rating($rating == 1);
        if ($time)
            $builder->order_by_time($time == 1);
        if ($fav)
            $builder->in_favourite();
        if ($page)
            $builder->page($page);
        $builder->limit($limit);

        // $builder->select('mt.rating_avg');
        // $builder->of_genres([3,4]);

        // $builder->of_latest(4);
        // $builder->order_by_rating();
        // $builder->order_by_time();

        // $builder->in_favourite();

        // print_r($query->toSql());
        // print_r($builder->get_as_book());
        // print_r($builder->debug_get_sql());
        // echo "<br>";
        // echo "<br>";
        // print_r($query->params());

        // DB::connection('mysql2');

        
        $maxPage = ($builder->count_unique() - 1) / $limit + 1;
        // echo $builder->count_unique();
        // echo '<br/>';
        // echo $limit;
        // echo '<br/>';
        // echo $page;
        // echo '<br/>';
        // print_r($this->getPagination($page, $maxPage));
        // echo '<br/>';

        
        // global $post;
        // echo $wp->request;
        // echo '<br/>';
        // echo home_url($wp->request);
        // echo '<br/>';
        // print_r($wp->query_vars);
        // echo '<br/>';
        // print_r(get_post_type());
        // echo '<br/>';
        // print_r(get_post_types());
        // echo '<br/>';
        // print_r($wp->query_vars);
        // echo '<br/>';
        // echo add_query_arg($wp->query_vars, home_url());
        // echo '<br/>';
        // $query_args = $wp->query_vars;
        // print_r($query_args);
        // $page = array_key_exists('page', $query_args) ? $query_args['page'] : 1;
        // $query_args['page'] = $page + 1;
        // print_r($query_args);
        // echo '<br/>';
        // echo add_query_arg($wp->query_vars, user_trailingslashit(home_url($wp->request), 'post'));
        // echo '<br/>';
        // echo get_permalink();
        // echo '<br/>';

        function filterItem($value, $content, $queryKey = null)
        {
            return [
                'value' => $value,
                'content' => $content,
                'queryKey' => $queryKey,
            ];
        }


        return [
            'page' => $page,
            'books' => $builder->get_as_book(),
            'filters' =>
            [
                [
                    'title' => '类别',
                    'key' => 'genre',
                    // 'queryKey' => KBP_QS_FILTER_GENRE,
                    'items' => array_map(function ($term) {
                        return filterItem($term->term_id, $term->name, QueryData::KBP_QS_FILTER_GENRE);
                    }, BookQuery::allBookGenres())
                ],
                [
                    'title' => '最后更新',
                    'key' => 'latest',
                    // 'queryKey' => KBP_QS_FILTER_LATEST,
                    'items' => [
                        filterItem(1, '3日内', QueryData::KBP_QS_FILTER_LATEST),
                        filterItem(2, '7日内', QueryData::KBP_QS_FILTER_LATEST),
                        filterItem(3, '半月内', QueryData::KBP_QS_FILTER_LATEST),
                        filterItem(4, '一月内', QueryData::KBP_QS_FILTER_LATEST),
                        filterItem(5, '三月内', QueryData::KBP_QS_FILTER_LATEST),
                    ]
                ],
                [
                    'title' => '个人',
                    'key' => 'personal',
                    // 'queryKey' => KBP_QS_FILTER_LATEST,
                    'items' => [
                        filterItem('1', '我的收藏', QueryData::KBP_QS_FILTER_IN_FAVORITE),
                    ]
                ],
                [
                    'title' => '排序',
                    'key' => 'sorting',
                    // 'queryKey' => KBP_QS_FILTER_LATEST,
                    'items' => [
                        filterItem('asc', '评分', QueryData::KBP_QS_FILTER_RATING),
                        filterItem('asc', '时间', QueryData::KBP_QS_FILTER_TIME),
                    ]
                ]
            ],
            'pagination' => $this->getPagination($page, $maxPage)
        ];
    }

    function getPagination(int $curr, int $end, int $maxLen = 3): array
    {
        $getPageUrl = function ($page) {
            global $wp;
            $query_args = $wp->query_vars;
            $query_args['page'] = $page;
            return add_query_arg($query_args, user_trailingslashit(home_url($wp->request)));
        };

        // 不够2n+1页
        if (1 + $maxLen > $end - $maxLen)
            $curr = 1;
        else {
            // $end = min($end, $curr + $maxLen);
            // $curr = max(1, $curr - $maxLen);
            // $total = $maxLen * 2;
            $pointer = max(1 + $maxLen, min($end - $maxLen, $curr));
            $curr = $pointer - $maxLen;
            $end = $pointer + $maxLen;
            // $end = min($end - $maxLen, $curr + $maxLen);  // [?, n - 3]
            // $curr = max(1 + $maxLen, $curr - $maxLen); // [1 + 3, ?]
        }
        $res = [];

        for (; $curr <= $end; $curr++)
            $res[] = [
                'page' => $curr,
                'url' => $getPageUrl($curr),
            ];
        return $res;
    }
}
