<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Processing fee (basis points)
    |--------------------------------------------------------------------------
    | Fee charged on every customer payment, in basis points of the gross
    | amount. 150 bp = 1.5%. Kept in config so the business rule lives in
    | exactly one place.
    */
    'fee_basis_points' => (int) env('KUDI_FEE_BASIS_POINTS', 150),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    | All amounts are stored as integer minor units (pesewas). The frontend
    | owns display formatting; this is exposed for API consumers.
    */
    'currency' => env('KUDI_CURRENCY', 'GHS'),

    /*
    |--------------------------------------------------------------------------
    | Simulated gateway success rate
    |--------------------------------------------------------------------------
    | Odds that a pending payment resolves as successful when processed,
    | expressed as [chances, out_of].
    */
    'gateway_success_odds' => [9, 10],
];
