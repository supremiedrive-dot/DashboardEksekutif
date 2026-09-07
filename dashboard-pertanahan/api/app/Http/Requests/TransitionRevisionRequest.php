<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class TransitionRevisionRequest extends StrictApiRequest
{
    public function rules(): array
    {
        return ['expected_revision'=>['required','integer','min:1'], 'reason'=>['nullable','string','max:1000']];
    }
    public function after(): array { return [fn (Validator $v) => $this->rejectUnknown(array_keys($this->rules()))]; }
}
