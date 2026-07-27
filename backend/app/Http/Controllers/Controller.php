<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Page size for list endpoints, clamped so a caller cannot request the
     * whole table in one go.
     */
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return min(max($request->integer('per_page', $default), 1), $max);
    }

    /**
     * Resolve `sort`/`direction` against a whitelist.
     *
     * The map is the whitelist: a caller names a column in the language of the
     * table it is looking at ("gross"), never a raw database column, so no
     * request can reach an unindexed or private field.
     *
     * @param  array<string, string>  $sortable
     * @return array{0: string, 1: string}
     */
    protected function sort(Request $request, array $sortable, string $default): array
    {
        $key = $request->string('sort')->toString();
        $column = $sortable[$key] ?? $default;
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        return [$column, $direction];
    }
}
