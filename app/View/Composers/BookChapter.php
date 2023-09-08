<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use KarsonJo\BookPost;
use KarsonJo\BookPost\BookContents;
use KarsonJo\BookPost\SqlQuery\BookQuery;

use function Roots\bundle;

class BookChapter extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var string[]
     */
    protected static $views = [
        'partials.content-book-chapter'
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    public function override()
    {
        wp_enqueue_style('reader-themes', get_template_directory_uri() . '/resources/styles/reader-themes.css');
        bundle('reader')->enqueue();


        $chapter = get_post();
        $book = BookQuery::rootPost($chapter);
        $contents = new BookContents($chapter);
        $prev_chapter = $contents->previousChapter();
        $next_chapter = $contents->nextChapter();
        // print_r($contents);
        // print_r($next_chapter);
        return [
            'contents' => $contents,
            'bookUrl' => get_permalink($book),
            'bookName' => $book->post_title,
            'authorUrl' => get_author_posts_url($book->post_author),
            'author' => get_the_author_meta('display_name', $book->post_author),
            'postDateTime' => $chapter->post_date,
            'preChapterUrl' => $prev_chapter ? get_permalink($prev_chapter->ID) : "#",
            'nextChapterUrl' => $next_chapter ? get_permalink($next_chapter->ID) : "#",
        ];
    }
}
