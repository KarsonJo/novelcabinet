<?php

namespace App\View\Components;

use Illuminate\View\Component;

class BookStyle1 extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public $coverImage;
    public function __construct($coverSrc = null)
    {
        $this->coverImage = $coverSrc;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.book-style1');
    }
}
