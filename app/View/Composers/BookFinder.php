<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;


use Latitude\QueryBuilder\Engine\CommonEngine;
use Latitude\QueryBuilder\QueryFactory;

use function KarsonJo\BookPost\SqlQuery\get_book_of_genres_sql;
use function KarsonJo\BookPost\SqlQuery\get_books_sql;
use function Latitude\QueryBuilder\field;

use KarsonJo\BookPost;
use KarsonJo\BookPost\SqlQuery\BookFilterBuilder;
use KarsonJo\BookRequest\QueryData;
use TenQuality\WP\Database\QueryBuilder;

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

        $genre = QueryData\get_filter_genre();
        $latest = QueryData\get_filter_latest();
        $rating = QueryData\get_filter_rating_sorting();
        $time = QueryData\get_filter_time_sorting();
        $fav = QueryData\get_filter_in_favorite();
        $page = max(QueryData\get_filter_page(), 1);
        $limit = QueryData\get_filter_limit();
        $limit = max(1, min(100, $limit ? $limit : 6));

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
        // $builder = get_books_sql();
        // $builder->of_genres([3,4]);

        // $builder->of_latest(4);
        // $builder->order_by_rating();
        // $builder->order_by_time();

        // $builder->in_favourite();

        // get_book_of_genres_sql($builder,[3,4]);
        // print_r($query->toSql());
        // print_r($builder->get_as_book());
        // print_r($builder->debug_get_sql());
        // echo "<br>";
        // echo "<br>";
        // print_r($query->params());

        // DB::connection('mysql2');

        $maxPage = ($builder->count() - 1) / $limit + 1;
        echo $maxPage;
        echo '<br/>';
        echo $page;
        echo '<br/>';
        print_r($this->getPagination($page, $maxPage));
        echo '<br/>';

        global $wp;
        // global $post;
        echo $wp->request;
        echo '<br/>';
        echo home_url($wp->request);
        echo '<br/>';
        print_r($wp->query_vars);
        echo '<br/>';
        print_r(get_post_type());
        echo '<br/>';
        print_r(get_post_types());
        echo '<br/>';
        print_r($wp->query_vars);
        echo '<br/>';
        echo add_query_arg($wp->query_vars, home_url());
        echo '<br/>';
        $query_args = $wp->query_vars;
        $page = array_key_exists('page', $query_args) ? $query_args['page'] : 1;
        $query_args['page'] = $page + 1;
        print_r($query_args);
        echo '<br/>';
        echo add_query_arg($wp->query_vars, user_trailingslashit(home_url($wp->request), 'post'));
        echo '<br/>';
        echo get_permalink();
        echo '<br/>';

        function filterItem($value, $content, $queryKey = null)
        {
            return [
                'value' => $value,
                'content' => $content,
                'queryKey' => $queryKey,
            ];
        }


        return [
            // 'genres' => BookPost\get_all_genres(),
            // 'updateTimes' => [1 => '3日内', 2 =>  '7日内', 3 => '半月内', 4 => '一月内', 5 => '三月内'],
            'page' => $page,
            'books' => $builder->get_as_book(),
            'filters' =>
            [
                [
                    'title' => '类别',
                    'key' => 'genre',
                    // 'queryKey' => KBP_QS_FILTER_GENRE,
                    'items' => array_map(function ($term) {
                        return filterItem($term->term_id, $term->name, KBP_QS_FILTER_GENRE);
                    }, BookPost\get_all_genres())
                ],
                [
                    'title' => '最后更新',
                    'key' => 'latest',
                    // 'queryKey' => KBP_QS_FILTER_LATEST,
                    'items' => [
                        filterItem(1, '3日内', KBP_QS_FILTER_LATEST),
                        filterItem(2, '7日内', KBP_QS_FILTER_LATEST),
                        filterItem(3, '半月内', KBP_QS_FILTER_LATEST),
                        filterItem(4, '一月内', KBP_QS_FILTER_LATEST),
                        filterItem(5, '三月内', KBP_QS_FILTER_LATEST),
                    ]
                ],
                [
                    'title' => '个人',
                    'key' => 'personal',
                    // 'queryKey' => KBP_QS_FILTER_LATEST,
                    'items' => [
                        filterItem('1', '我的收藏', KBP_QS_FILTER_IN_FAVORITE),
                    ]
                ],
                [
                    'title' => '排序',
                    'key' => 'sorting',
                    // 'queryKey' => KBP_QS_FILTER_LATEST,
                    'items' => [
                        filterItem('asc', '评分', KBP_QS_FILTER_RATING),
                        filterItem('asc', '时间', KBP_QS_FILTER_TIME),
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

    // private function getFilterBlock()
    // {
    //     function filterItem($value, $content)
    //     {
    //         return [
    //             'value' => $value,
    //             'content' => $content,
    //         ];
    //     }

    //     return [
    //         [
    //             'title' => '类别',
    //             'key' => 'genre',
    //             'queryKey' => KBP_QS_FILTER_GENRE,
    //             'items' => array_map(function ($genre) {
    //                 return filterItem($genre->term_id, $genre->name);
    //             }, BookPost\get_all_genres())
    //         ],
    //         [
    //             'title' => '最后更新',
    //             'key' => 'latest',
    //             'queryKey' => KBP_QS_FILTER_LATEST,
    //             'items' => [
    //                 filterItem(1, '3日内'),
    //                 filterItem(2, '7日内'),
    //                 filterItem(3, '半月内'),
    //                 filterItem(4, '一月内'),
    //                 filterItem(5, '三月内'),
    //             ]
    //         ]
    //     ];
    // }
}
