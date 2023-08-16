<?php

namespace App\View\Components;

use Illuminate\View\Component;
use KarsonJo\BookPost\Book;
use KarsonJo\BookPost\SqlQuery\BookFilterBuilder;

class BookListItemMain extends Component
{
    public function __construct(public $book)
    {

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
