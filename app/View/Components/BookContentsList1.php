<?php

namespace App\View\Components;

use Illuminate\View\Component;
use KarsonJo\BookPost;
use KarsonJo\BookPost\BookContents;
use WP_Post;

class BookContentsList1 extends Component
{
    public BookContents $contents;
    public array $volumes;
    public bool $hasContent;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(BookContents $contents, WP_Post $book = null)
    {
        if ($contents)
            $this->contents = $contents;
        else {
            if (!$book)
                $book = get_post($book);
            $this->contents = new BookContents($book);
        }
        // $volumes = BookPost\get_book_volume_chapters($book->ID);

        // if (array_key_exists(0, $volumes) && array_key_exists(1, $volumes[0]))
        //     $first_chapter = $volumes[0][1];
        $this->volumes = $this->contents->get_volumes();
        $this->hasContent = boolval($this->contents->get_first_chapter());

        // global $post;
        // $this->contents->set_active_chapter($post);
        // echo 123;
        // print_r($this->contents->locate_chapter($post));
        // print_r($this->contents->previous_chapter());
        // print_r($this->contents->next_chapter());
        // echo 123;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.book-contents-list1');
    }
}
