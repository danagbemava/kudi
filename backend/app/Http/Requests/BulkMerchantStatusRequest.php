<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkMerchantStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'exists:merchants,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Choose at least one merchant.',
            'ids.max' => 'Update at most 200 merchants at a time.',
            // Default reads "The selected ids.0 is invalid", which leaks an
            // array index into a message a person reads.
            'ids.*.exists' => 'One or more of the merchants selected no longer exists. Refresh and try again.',
            'ids.*.integer' => 'That selection could not be read. Refresh and try again.',
        ];
    }
}
