<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class CatalogueIndexRequest extends StrictApiRequest
{
    public function rules(): array
    {
        return ['as_of_date'=>['required','date_format:Y-m-d'], 'per_page'=>['sometimes','integer','min:1','max:100'],
            'page'=>['sometimes','integer','min:1']];
    }
    public function after(): array { return [fn (Validator $v) => $this->rejectUnknown(array_keys($this->rules()))]; }
}
