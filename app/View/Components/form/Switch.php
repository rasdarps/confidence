<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;

class Switch extends Component
{
    public $name;
    public $label;
    public $checked;
    public $value;
    public $help;
    public $id;
    
    public function __construct(
        $name,
        $label = null,
        $checked = false,
        $value = '1',
        $help = null,
        $id = null
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->checked = filter_var($checked, FILTER_VALIDATE_BOOLEAN) || old($name, $checked);
        $this->value = $value;
        $this->help = $help;
        $this->id = $id ?? $name;
    }
    
    public function render()
    {
        return view('components.form.switch');
    }
}