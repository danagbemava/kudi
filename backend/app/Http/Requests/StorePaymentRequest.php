<?php

namespace App\Http\Requests;

use App\Models\Merchant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'merchant_id' => ['required', 'integer', 'exists:merchants,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['required', 'string', 'max:40', 'unique:transactions,reference'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('merchant_id')) {
                    return;
                }

                $merchant = Merchant::find($this->integer('merchant_id'));

                if ($merchant && ! $merchant->isActive()) {
                    $validator->errors()->add(
                        'merchant_id',
                        'This merchant is inactive and cannot receive payments.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'The amount must be at least 1 pesewa.',
            'reference.unique' => 'This payment reference has already been used.',
        ];
    }
}
