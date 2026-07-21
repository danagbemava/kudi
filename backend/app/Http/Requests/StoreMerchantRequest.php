<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMerchantRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:merchants,email'],
            'business_name' => ['required', 'string', 'max:160'],
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'account_number' => ['required', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A merchant with this email is already registered.',
            'institution_id.exists' => 'Please choose a valid bank or mobile money provider.',
        ];
    }
}
