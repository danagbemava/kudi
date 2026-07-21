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
}
