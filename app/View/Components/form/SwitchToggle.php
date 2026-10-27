<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;

class SwitchToggle extends Component
{
    public $name;
    public $label;
    public $checked;
    public $value;
    public $help;
    public $id;
    public $onText;
    public $offText;
    
    public function __construct(
        $name,
        $label = null,
        $checked = false,
        $value = '1',
        $help = null,
        $id = null,
        $onText = 'Yes',
        $offText = 'No'
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->checked = filter_var($checked, FILTER_VALIDATE_BOOLEAN) || old($name, $checked);
        $this->value = $value;
        $this->help = $help;
        $this->id = $id ?? $name;
        $this->onText = $onText;
        $this->offText = $offText;
    }
    
    public function render()
    {
        return view('components.form.switch-toggle');
    }
}