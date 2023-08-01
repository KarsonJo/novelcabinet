<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use KarsonJo\BookPost;
use KarsonJo\BookPost\Book;

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

        $this_book = Book::initBookFromPost($book->ID);
        $contents = $this_book->contents;
        $first_chapter = $contents->get_first_chapter();
        $has_content = boolval($first_chapter);
        

        return [
            'contents' => $contents,
            'volumes' => $contents->get_volumes(),
            'hasContent' => $has_content,
            'readingLink' => $has_content ? get_permalink($first_chapter->ID) : '#',
            'book' => $this_book,
        ];
    }
}
