<?php

namespace App\View\Components;

use Illuminate\View\Component;

class HeadlineStyle1 extends Component
{
    public $title;
    public $link;
    public $more;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($title = 'The title', $link = null, $more = null)
    {
        $this->title = $title;
        $this->link = $link;
        $this->more = $more;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.headline-style1');
    }
}
