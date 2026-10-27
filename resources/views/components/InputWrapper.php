<?php

namespace App\View\Components;

use Illuminate\View\Component;

class InputWrapper extends Component
{
    public string $wrapperClass;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(string $wrapperClass)
    {
        $this->wrapperClass = $wrapperClass;
    }
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.input-wrapper');
    }
}
