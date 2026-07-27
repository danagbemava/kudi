<?php

namespace App\Http\Requests;

use App\Enums\TransactionStatus;
use Illuminate\Validation\Rule;

class TransactionIndexRequest extends DateRangeRequest
{
    /** Columns a caller may order by, mapped to the column actually sorted. */
    public const SORTABLE = [
        'date' => 'created_at',
        'reference' => 'reference',
        'gross' => 'gross_amount',
        'fee' => 'fee_amount',
        'net' => 'net_amount',
        'status' => 'status',
    ];

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TransactionStatus::class)],
            'merchant_id' => ['nullable', 'integer', 'exists:merchants,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTABLE))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'merchant_id.exists' => 'That merchant no longer exists.',
        ]);
    }
}
