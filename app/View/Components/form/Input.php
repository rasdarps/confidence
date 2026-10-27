<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;

class Input extends Component
{
    public $name;
    public $label;
    public $type;
    public $value;
    public $required;
    public $placeholder;
    public $help;
    public $id;
    
    public function __construct(
        $name, 
        $label = null, 
        $type = 'text', 
        $value = null, 
        $required = false,
        $placeholder = null,
        $help = null,
        $id = null
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->value = $value;
        $this->required = filter_var($required, FILTER_VALIDATE_BOOLEAN);
        $this->placeholder = $placeholder;
        $this->help = $help;
        $this->id = $id ?? $name;
    }
    
    public function render()
    {
        return view('components.form.input');
    }
}