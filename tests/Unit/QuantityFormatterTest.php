<?php

use App\Support\QuantityFormatter;

describe('formatting ingredient quantities', function () {
    it('removes unnecessary decimal places for whole numbers', function () {
        expect(QuantityFormatter::format(8.0))->toBe(8)
            ->and(QuantityFormatter::format(8.00))->toBe(8);
    });

    it('keeps meaningful fractional quantities', function () {
        expect(QuantityFormatter::format(1.5))->toBe(1.5)
            ->and(QuantityFormatter::format(0.33))->toBe(0.33);
    });
});
