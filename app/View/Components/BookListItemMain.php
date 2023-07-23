<?php

namespace App\View\Components;

use Illuminate\View\Component;


class BookListItemMain extends Component
{
    public $coverSrc;
    public $permalink;
    public $title;
    public $author;
    public $excerpt;
    public $tags;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($bookPost = null, $bookGenre = null)
    {
        if ($bookPost == null) $bookPost = get_post();

        $images = wp_get_attachment_image_src(get_post_thumbnail_id($bookPost->ID), "full");
        $this->coverSrc = is_array($images) ? $images[0] : "";
        $this->permalink = get_permalink($bookPost);
        $this->title = $bookPost->post_title;
        $this->author = get_the_author_meta('display_name', $bookPost->post_author);
        $this->excerpt = $bookPost->post_excerpt;

        $this->tags = get_the_terms($bookPost, $bookGenre ?? defined('KBP_BOOK_GENRE') ? KBP_BOOK_GENRE: "category");
        // write_log($this->tags);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.book-list-item-main');
    }
}
