<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;

class Textarea extends Component
{
    public $name;
    public $label;
    public $value;
    public $required;
    public $rows;
    public $placeholder;
    public $help;
    public $id;
    public $isEditor;
    
    public function __construct(
        $name,
        $label = null,
        $value = null,
        $required = false,
        $rows = 3,
        $placeholder = null,
        $help = null,
        $id = null,
        $editor = false
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->value = $value;
        $this->required = filter_var($required, FILTER_VALIDATE_BOOLEAN);
        $this->rows = $rows;
        $this->placeholder = $placeholder;
        $this->help = $help;
        $this->id = $id ?? $name;
        $this->isEditor = filter_var($editor, FILTER_VALIDATE_BOOLEAN);
    }
    
    public function render()
    {
        return view('components.form.textarea');
    }
}