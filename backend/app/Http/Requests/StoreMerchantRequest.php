<?php

namespace App\Http\Requests;

use App\Models\Institution;
use App\Support\AccountNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreMerchantRequest extends FormRequest
{
    /**
     * Account numbers get pasted in whatever grouping the merchant was given
     * them in. Strip that before validating, so "024 123 4567" is judged as the
     * number it is and stored in one canonical form.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('account_number')) {
            $this->merge([
                'account_number' => AccountNumber::normalise($this->input('account_number')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:merchants,email'],
            'business_name' => ['required', 'string', 'max:160'],
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'account_number' => ['required', 'string', 'max:30', $this->accountNumberRule()],
        ];
    }

    /**
     * What counts as a valid account number depends on the institution chosen,
     * so the rule has to resolve it rather than being a static pattern. If the
     * institution is missing or unknown its own rule reports that, and this one
     * stands aside instead of adding a second, more confusing error.
     */
    private function accountNumberRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $institution = Institution::find($this->input('institution_id'));

            if ($institution && ! AccountNumber::isValidFor($institution->type, (string) $value)) {
                $fail(AccountNumber::hint($institution->type));
            }
        };
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A merchant with this email is already registered.',
            'institution_id.exists' => 'Please choose a valid bank or mobile money provider.',
        ];
    }
}
