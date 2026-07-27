<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class SettlementIndexRequest extends DateRangeRequest
{
    /** Columns a caller may order by, mapped to the column actually sorted. */
    public const SORTABLE = [
        'date' => 'settled_at',
        'reference' => 'reference',
        'batch' => 'batch_ref',
        'amount' => 'amount',
    ];

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'batch' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTABLE))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
    }
}
