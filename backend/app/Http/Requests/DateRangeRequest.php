<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared validation for the `from`/`to` filters every ledger-shaped endpoint
 * accepts.
 *
 * Without this, `Request::date()` hands an unparseable string straight to
 * Carbon, which throws — turning a mistyped query parameter into a 500. These
 * are reachable from a URL, a bookmark or any API client, not just from the
 * date pickers, so they get validated like any other input.
 *
 * `after_or_equal` matters as much as `date` does: a reversed range is not a
 * crash but it is nonsense, and it used to produce a series running backwards
 * through time.
 */
class DateRangeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    public function messages(): array
    {
        return [
            'from.date' => 'The "from" date could not be read. Use a date like 2026-07-01.',
            'to.date' => 'The "to" date could not be read. Use a date like 2026-07-31.',
            'to.after_or_equal' => 'The "to" date cannot be earlier than the "from" date.',
        ];
    }
}
