<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class StoreManualObservationRequest extends StrictApiRequest
{
    public function rules(): array
    {
        return [
            'indicator_code'=>['required','string','max:80'], 'region_id'=>['required','integer'],
            'as_of_date'=>['required','date_format:Y-m-d'], 'value'=>['required'],
            'note'=>['nullable','string','max:1000'],
        ];
    }
    public function after(): array { return [fn (Validator $v) => $this->rejectUnknown(array_keys($this->rules()))]; }
}
