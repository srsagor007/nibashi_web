<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use App\Models\QuestionFieldType;
use Livewire\Component;

class FormQuestions extends Component
{
    public Form $form;

    public $field_types;

    public function mount()
    {
        $this->field_types = QuestionFieldType::all();
    }

    public function render()
    {
        return view('livewire.forms.form-questions');
    }
}
