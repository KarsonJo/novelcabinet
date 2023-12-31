<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Tag extends Component
{
    public $tag;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($tag = null)
    {
        $this->tag = $tag;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.tag');
    }
}
