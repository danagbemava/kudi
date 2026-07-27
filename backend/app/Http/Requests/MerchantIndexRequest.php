<?php

namespace App\Http\Requests;

use App\Enums\MerchantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MerchantIndexRequest extends FormRequest
{
    /**
     * Columns a caller may order by. `balance` sorts on the joined wallet
     * rather than a merchants column, which the controller handles explicitly.
     */
    public const SORTABLE = [
        'registered' => 'merchants.created_at',
        'business' => 'merchants.business_name',
        'contact' => 'merchants.name',
        'status' => 'merchants.status',
        'balance' => 'wallets.balance',
    ];

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(MerchantStatus::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTABLE))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
