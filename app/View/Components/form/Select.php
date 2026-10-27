<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;

class Select extends Component
{
    public $name;
    public $label;
    public $options;
    public $value;
    public $required;
    public $placeholder;
    public $help;
    public $id;
    
    public function __construct(
        $name,
        $label = null,
        $options = [],
        $value = null,
        $required = false,
        $placeholder = 'Select Option',
        $help = null,
        $id = null
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->options = $options;
        $this->value = $value;
        $this->required = filter_var($required, FILTER_VALIDATE_BOOLEAN);
        $this->placeholder = $placeholder;
        $this->help = $help;
        $this->id = $id ?? $name;
    }
    
    public function render()
    {
        return view('components.form.select');
    }
}