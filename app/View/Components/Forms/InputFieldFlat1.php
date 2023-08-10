<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

class InputFieldFlat1 extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        public string $title = '',
        public string $for = '',
        public array $message = []
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.forms.input-field-flat1');
    }
}
