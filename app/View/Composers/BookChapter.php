<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use KarsonJo\BookPost;

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
        global $post;
        $book = BookPost\get_book_from_post($post);
        $contents = BookPost\get_book_contents($post);
        $prev_chapter = $contents->previous_chapter();
        $next_chapter = $contents->next_chapter();
        return [
            'contents' => $contents,
            'bookUrl' => get_permalink($book),
            'bookName' => $book->post_title,
            'authorUrl' => get_author_posts_url($book->post_author),
            'author' => get_the_author_meta('display_name', $book->post_author),
            'postDateTime' => $book->post_date,
            'preChapterUrl' => $prev_chapter ? get_permalink($prev_chapter->ID) : "#",
            'nextChapterUrl' => $next_chapter ? get_permalink($next_chapter->ID) : "#",
        ];
    }
}
