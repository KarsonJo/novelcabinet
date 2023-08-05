<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SwitchButton extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(public ?string $id = null, public ?string $name = null)
    {
        if ($this->id === null)
            $this->id = 'rand-' . \mt_rand();
        if ($this->name === null)
            $this->name = $this->id;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.switch-button');
    }
}
