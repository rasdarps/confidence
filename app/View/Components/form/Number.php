<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;

class Number extends Component
{
    public $name;
    public $label;
    public $value;
    public $required;
    public $step;
    public $min;
    public $max;
    public $placeholder;
    public $help;
    public $id;
    
    public function __construct(
        $name,
        $label = null,
        $value = null,
        $required = false,
        $step = '1',
        $min = null,
        $max = null,
        $placeholder = null,
        $help = null,
        $id = null
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->value = $value;
        $this->required = filter_var($required, FILTER_VALIDATE_BOOLEAN);
        $this->step = $step;
        $this->min = $min;
        $this->max = $max;
        $this->placeholder = $placeholder;
        $this->help = $help;
        $this->id = $id ?? $name;
    }
    
    public function render()
    {
        return view('components.form.number');
    }
}