<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class StrictApiRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function rejectUnknown(array $allowed): void
    {
        $unknown = array_diff(array_keys($this->all()), $allowed);
        if ($unknown) $this->validator->errors()->add('request', 'Field tidak diizinkan: '.implode(', ', $unknown));
    }
}
