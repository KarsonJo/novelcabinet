<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Checkbox1 extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public string $value = "checked",
        public $checked = false,
    ) {
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
        return view('components.checkbox1');
    }
}
