<?php

use App\Support\Money;

describe('Money::fee', function () {
    it('computes a 1.5% fee on an exact amount', function () {
        // GH₵100.00 = 10,000 pesewas → fee GH₵1.50 = 150 pesewas
        expect(Money::fee(10_000))->toBe(150);
    });

    it('rounds half-up when the fee is fractional', function () {
        // GH₵1.00 = 100 pesewas → 1.5 pesewas → rounds up to 2
        expect(Money::fee(100))->toBe(2);
    });

    it('rounds down below the midpoint', function () {
        // 99 pesewas → 1.485 pesewas → rounds to 1
        expect(Money::fee(99))->toBe(1);
    });

    it('handles zero', function () {
        expect(Money::fee(0))->toBe(0);
    });

    it('uses pure integer math on large amounts', function () {
        // GH₵10,000,000.00 = 1,000,000,000 pesewas → fee 15,000,000 exactly
        expect(Money::fee(1_000_000_000))->toBe(15_000_000);
    });
});
