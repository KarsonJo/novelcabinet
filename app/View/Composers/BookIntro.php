<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use KarsonJo\BookPost;

class BookIntro extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var string[]
     */
    protected static $views = [
        'partials.content-book-intro'
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    public function override()
    {
        $book = get_post();
        // $volumes = BookPost\get_book_volume_chapters($book->ID);
        
        // if (array_key_exists(0, $volumes) && array_key_exists(1, $volumes[0]))
        //     $first_chapter = $volumes[0][1];

        $contents = BookPost\get_book_contents($book);
        $first_chapter = $contents->get_first_chapter();
        $has_content = boolval($first_chapter);

        return [
            'bookName' => $book->post_title,
            'author' => get_the_author_meta('display_name', $book->post_author),
            'coverSrc' => BookPost\get_book_cover($book->ID),
            'contents' => $contents,
            'volumes' => $contents->get_volumes(),
            'tags' => BookPost\get_book_genres($book->ID),
            'excerpt' => $book->post_excerpt,
            'hasContent' => $has_content,
            'readingLink' => $has_content ? get_permalink($first_chapter->ID) : '#'
        ];
    }
}
